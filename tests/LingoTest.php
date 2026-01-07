<?php

use Kanekescom\Lingo\Lingo;

describe('Lingo static methods', function () {
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
        expect($result['key1'])->toBe('second');
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
            'World' => 'World',
            'Goodbye' => 'Goodbye',
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
            'Goodbye' => 'Goodbye',
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
            'Goodbye' => 'Goodbye',
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
            'another' => 'value',
        ];

        $cleaned = Lingo::clean($translations);

        expect($cleaned)->toHaveCount(3);
        expect(array_key_first($cleaned))->toBe('a');
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
        expect($result['Goodbye'])->toBe('Goodbye');
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
        $tempDir = sys_get_temp_dir() . '/lingo-test-' . getmypid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $filePath = $tempDir . '/test.json';
        $translations = ['Hello' => 'Halo', 'World' => 'Dunia'];

        $saved = Lingo::save($filePath, $translations);
        expect($saved)->toBeTrue();
        expect(file_exists($filePath))->toBeTrue();

        $loaded = Lingo::load($filePath);
        expect($loaded)->not->toBeNull();
        expect($loaded['translations'])->toBe(Lingo::sortKeys($translations));

        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('returns null when loading non-existent file', function () {
        $result = Lingo::load('/non/existent/file.json');

        expect($result)->toBeNull();
    });

    it('can scan directory for translation keys', function () {
        $tempDir = sys_get_temp_dir() . '/lingo-scan-' . getmypid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $phpContent = "<?php echo __('Test Key'); echo trans('Another Key');";
        file_put_contents($tempDir . '/test.php', $phpContent);

        $keys = Lingo::scanDirectory($tempDir);

        expect($keys)->toContain('Test Key');
        expect($keys)->toContain('Another Key');

        @unlink($tempDir . '/test.php');
        @rmdir($tempDir);
    });

    it('can check if has untranslated items', function () {
        $hasUntranslated = Lingo::hasUntranslated(['Hello' => 'Hello']);
        $noUntranslated = Lingo::hasUntranslated(['Hello' => 'Halo']);

        expect($hasUntranslated)->toBeTrue();
        expect($noUntranslated)->toBeFalse();
    });

    it('can check if has missing keys', function () {
        $translations = ['Hello' => 'Halo'];
        $keys = ['Hello', 'World'];

        expect(Lingo::hasMissing($translations, $keys))->toBeTrue();
        expect(Lingo::hasMissing($translations, ['Hello']))->toBeFalse();
    });

    it('can check if has unused keys', function () {
        $translations = ['Hello' => 'Halo', 'Unused' => 'Test'];
        $usedKeys = ['Hello'];

        expect(Lingo::hasUnused($translations, $usedKeys))->toBeTrue();
        expect(Lingo::hasUnused(['Hello' => 'Halo'], ['Hello']))->toBeFalse();
    });

    it('returns empty array for non-existent scan directory', function () {
        $keys = Lingo::scanDirectory('/non/existent/directory');

        expect($keys)->toBe([]);
    });

    it('returns null when loading invalid JSON file', function () {
        $tempDir = sys_get_temp_dir() . '/lingo-invalid-' . getmypid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $filePath = $tempDir . '/invalid.json';
        file_put_contents($filePath, 'not valid json {{{');

        $result = Lingo::load($filePath);

        expect($result)->toBeNull();

        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('handles unicode characters in translations', function () {
        $translations = [
            'Hello' => 'Halo',
            '日本語' => '日本語翻訳',
            'Emoji 🎉' => 'Emoji translated 🎊',
        ];

        $json = Lingo::toJson($translations, false); // Don't sort keys
        $decoded = json_decode($json, true);

        expect($decoded)->toBe($translations);
        expect($decoded['日本語'])->toBe('日本語翻訳');
        expect($decoded['Emoji 🎉'])->toBe('Emoji translated 🎊');
    });

    it('handles empty translations in stats correctly', function () {
        $stats = Lingo::stats([]);

        expect($stats['total'])->toBe(0);
        expect($stats['translated'])->toBe(0);
        expect($stats['untranslated'])->toBe(0);
        expect($stats['percentage'])->toBe(0);
    });

    it('handles 100 percent translated stats', function () {
        $translations = [
            'Hello' => 'Halo',
            'World' => 'Dunia',
        ];

        $stats = Lingo::stats($translations);

        expect($stats['percentage'])->toBe(100.0);
    });

    it('can resolve relative path to base_path', function () {
        $resolved = Lingo::resolvePath('app/Models');

        expect($resolved)->toBe(base_path('app/Models'));
    });

    it('can scan single file for translation keys', function () {
        $tempDir = sys_get_temp_dir() . '/lingo-scan-file-' . getmypid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $phpContent = "<?php echo __('FileKey'); echo trans('AnotherKey');";
        file_put_contents($tempDir . '/single.php', $phpContent);

        $keys = Lingo::scan($tempDir . '/single.php');

        expect($keys)->toContain('FileKey');
        expect($keys)->toContain('AnotherKey');

        @unlink($tempDir . '/single.php');
        @rmdir($tempDir);
    });

    it('can scan directory using scan method', function () {
        $tempDir = sys_get_temp_dir() . '/lingo-scan-dir-' . getmypid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $phpContent = "<?php echo __('DirKey');";
        file_put_contents($tempDir . '/test.php', $phpContent);

        $keys = Lingo::scan($tempDir);

        expect($keys)->toContain('DirKey');

        @unlink($tempDir . '/test.php');
        @rmdir($tempDir);
    });

    it('returns empty array for non-existent path in scan', function () {
        $keys = Lingo::scan('/definitely/non/existent/path');

        expect($keys)->toBe([]);
    });
});
