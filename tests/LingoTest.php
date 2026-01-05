<?php

use Kanekescom\Lingo\Lingo;

describe('Lingo static methods', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/lingo-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    });

    afterEach(function () {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }

            rmdir($this->tempDir);
        }
    });

    it('can find duplicates in JSON content', function () {
        $json = '{"key1": "value1", "key2": "value2", "key1": "duplicate"}';

        $duplicates = Lingo::duplicates($json);

        expect($duplicates)->toHaveKey('key1');
        expect($duplicates['key1'])->toBe(2);
    });

    it('can detect when JSON has duplicates', function () {
        $withDuplicates = '{"key1": "value1", "key1": "value2"}';
        $withoutDuplicates = '{"key1": "value1", "key2": "value2"}';

        expect(Lingo::hasDuplicates($withDuplicates))->toBeTrue();
        expect(Lingo::hasDuplicates($withoutDuplicates))->toBeFalse();
    });

    it('can remove duplicates from JSON content', function () {
        $json = '{"key1": "first", "key2": "value2", "key1": "second"}';

        $result = Lingo::removeDuplicates($json);

        expect($result)->toHaveCount(2);
        expect($result['key1'])->toBe('second'); // PHP keeps last occurrence
    });

    it('can sort translations by keys ascending', function () {
        $translations = ['z' => 'last', 'a' => 'first', 'm' => 'middle'];

        $sorted = Lingo::sortKeys($translations, true);
        $keys = array_keys($sorted);

        expect($keys[0])->toBe('a');
        expect($keys[1])->toBe('m');
        expect($keys[2])->toBe('z');
    });

    it('can sort translations by keys descending', function () {
        $translations = ['z' => 'last', 'a' => 'first', 'm' => 'middle'];

        $sorted = Lingo::sortKeys($translations, false);
        $keys = array_keys($sorted);

        expect($keys[0])->toBe('z');
        expect($keys[1])->toBe('m');
        expect($keys[2])->toBe('a');
    });

    it('can find untranslated items', function () {
        $translations = [
            'Hello' => 'Halo',
            'World' => 'World', // untranslated
            'Goodbye' => 'Goodbye', // untranslated
        ];

        $untranslated = Lingo::untranslated($translations);

        expect($untranslated)->toHaveCount(2);
        expect($untranslated)->toHaveKey('World');
        expect($untranslated)->toHaveKey('Goodbye');
    });

    it('can find translated items', function () {
        $translations = [
            'Hello' => 'Halo',
            'World' => 'Dunia',
            'Goodbye' => 'Goodbye', // untranslated
        ];

        $translated = Lingo::translated($translations);

        expect($translated)->toHaveCount(2);
        expect($translated)->toHaveKey('Hello');
        expect($translated)->toHaveKey('World');
    });

    it('can get translation statistics', function () {
        $translations = [
            'Hello' => 'Halo',
            'World' => 'Dunia',
            'Goodbye' => 'Goodbye', // untranslated
        ];

        $stats = Lingo::stats($translations);

        expect($stats['total'])->toBe(3);
        expect($stats['translated'])->toBe(2);
        expect($stats['untranslated'])->toBe(1);
        expect($stats['percentage'])->toBe(66.67);
    });

    it('can clean translations', function () {
        $translations = [
            'z' => 'last',
            'a' => 'first',
            'empty' => '',
            'null' => null,
        ];

        $cleaned = Lingo::clean($translations);

        expect($cleaned)->toHaveCount(2);
        expect(array_key_first($cleaned))->toBe('a'); // Sorted
    });

    it('can export to JSON', function () {
        $translations = ['Hello' => 'Halo', 'World' => 'Dunia'];

        $json = Lingo::toJson($translations);

        expect($json)->toBeString();
        expect(json_decode($json, true))->toBe(Lingo::sortKeys($translations));
    });

    it('can extract translation keys from content', function () {
        $content = <<<'PHP'
        <?php
        echo __('Hello World');
        echo __("Welcome");
        echo trans('Goodbye');
        echo @lang('Message');
        echo Lang::get('Test');
        PHP;

        $keys = Lingo::extractKeys($content);

        expect($keys)->toContain('Hello World');
        expect($keys)->toContain('Welcome');
        expect($keys)->toContain('Goodbye');
        expect($keys)->toContain('Message');
        expect($keys)->toContain('Test');
    });

    it('can find missing translation keys', function () {
        $translations = ['Hello' => 'Halo', 'World' => 'Dunia'];
        $keys = ['Hello', 'Goodbye', 'Welcome'];

        $missing = Lingo::missing($translations, $keys);

        expect($missing)->toHaveCount(2);
        expect($missing)->toContain('Goodbye');
        expect($missing)->toContain('Welcome');
    });

    it('can add missing translation keys', function () {
        $translations = ['Hello' => 'Halo'];
        $keys = ['Hello', 'Goodbye'];

        $result = Lingo::addMissing($translations, $keys);

        expect($result)->toHaveCount(2);
        expect($result['Goodbye'])->toBe('Goodbye'); // Key as value
    });

    it('can find unused translation keys', function () {
        $translations = ['Hello' => 'Halo', 'Goodbye' => 'Selamat Tinggal', 'Unused' => 'Tidak Dipakai'];
        $usedKeys = ['Hello', 'Goodbye'];

        $unused = Lingo::unused($translations, $usedKeys);

        expect($unused)->toHaveCount(1);
        expect($unused)->toContain('Unused');
    });

    it('can remove unused translation keys', function () {
        $translations = ['Hello' => 'Halo', 'Unused' => 'Tidak Dipakai'];
        $usedKeys = ['Hello'];

        $result = Lingo::removeUnused($translations, $usedKeys);

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('Hello');
        expect($result)->not->toHaveKey('Unused');
    });

    it('can save and load translation files', function () {
        $filePath = $this->tempDir . '/test.json';
        $translations = ['Hello' => 'Halo', 'World' => 'Dunia'];

        $saved = Lingo::save($filePath, $translations);
        expect($saved)->toBeTrue();
        expect(file_exists($filePath))->toBeTrue();

        $loaded = Lingo::load($filePath);
        expect($loaded)->not->toBeNull();
        expect($loaded['translations'])->toBe(Lingo::sortKeys($translations));
    });

    it('returns null when loading non-existent file', function () {
        $result = Lingo::load('/non/existent/file.json');

        expect($result)->toBeNull();
    });

    it('can scan directory for translation keys', function () {
        // Create a test PHP file
        $phpContent = "<?php echo __('Test Key'); echo trans('Another Key');";
        file_put_contents($this->tempDir . '/test.php', $phpContent);

        $keys = Lingo::scanDirectory($this->tempDir);

        expect($keys)->toContain('Test Key');
        expect($keys)->toContain('Another Key');
    });
});
