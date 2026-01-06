<?php

/**
 * Helper to create a temp directory for testing.
 */
function createTempTestDir(): string
{
    $tempDir = sys_get_temp_dir() . '/lingo-cmd-' . getmypid() . '-' . uniqid();
    if (! is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    return $tempDir;
}

/**
 * Helper to clean up temp directory.
 */
function cleanupTempDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $files = glob($dir . '/*');
    foreach ($files as $file) {
        if (is_dir($file)) {
            cleanupTempDir($file);
        } else {
            @unlink($file);
        }
    }
    @rmdir($dir);
}

describe('LingoCheckCommand', function () {
    it('can check translation file for issues', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'World' => 'World'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:check', ['locale' => $filePath])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('reports error for non-existent file', function () {
        $this->artisan('lingo:check', ['locale' => '/non/existent/file.json'])
            ->assertFailed();
    });

    it('reports error for invalid JSON file', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/invalid.json';
        file_put_contents($filePath, 'not valid json {{{');

        $this->artisan('lingo:check', ['locale' => $filePath])
            ->assertFailed();

        cleanupTempDir($tempDir);
    });

    it('uses app locale when locale argument is omitted', function () {
        // Set app locale
        config(['app.locale' => 'test']);

        // Create lang directory and file
        $langDir = lang_path();
        if (! is_dir($langDir)) {
            mkdir($langDir, 0777, true);
        }

        $filePath = lang_path('test.json');
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:check')
            ->assertSuccessful();

        @unlink($filePath);
    });
});

describe('LingoCleanCommand', function () {
    it('can clean translation file', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode([
            'z' => 'last',
            'a' => 'first',
            'empty' => '',
        ], JSON_PRETTY_PRINT));

        $this->artisan('lingo:clean', ['locale' => $filePath])
            ->assertSuccessful();

        $cleaned = json_decode(file_get_contents($filePath), true);
        expect(array_keys($cleaned)[0])->toBe('a'); // Sorted
        expect($cleaned)->not->toHaveKey('empty'); // Empty removed

        cleanupTempDir($tempDir);
    });

    it('removes duplicates from file', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        // Create file with duplicate keys
        file_put_contents($filePath, '{"key1": "first", "key2": "value", "key1": "duplicate"}');

        $this->artisan('lingo:clean', ['locale' => $filePath])
            ->assertSuccessful();

        $cleaned = json_decode(file_get_contents($filePath), true);
        expect($cleaned)->toHaveCount(2);
        expect($cleaned['key1'])->toBe('duplicate'); // PHP keeps last

        cleanupTempDir($tempDir);
    });

    it('can keep empty values with --keep-empty', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode([
            'filled' => 'value',
            'empty' => '',
        ], JSON_PRETTY_PRINT));

        $this->artisan('lingo:clean', ['locale' => $filePath, '--keep-empty' => true])
            ->assertSuccessful();

        $cleaned = json_decode(file_get_contents($filePath), true);
        expect($cleaned)->toHaveKey('empty');

        cleanupTempDir($tempDir);
    });

    it('uses app locale when locale argument is omitted', function () {
        config(['app.locale' => 'clean-test']);

        $langDir = lang_path();
        if (! is_dir($langDir)) {
            mkdir($langDir, 0777, true);
        }

        $filePath = lang_path('clean-test.json');
        file_put_contents($filePath, json_encode(['z' => 'last', 'a' => 'first'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:clean')
            ->assertSuccessful();

        $cleaned = json_decode(file_get_contents($filePath), true);
        expect(array_keys($cleaned)[0])->toBe('a');

        @unlink($filePath);
    });
});

describe('LingoStatsCommand', function () {
    it('can show translation statistics', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'World' => 'World'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:stats', ['locale' => $filePath])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('can show detailed statistics', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'World' => 'World'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:stats', ['locale' => $filePath, '--detailed' => true])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('uses app locale when locale argument is omitted', function () {
        config(['app.locale' => 'stats-test']);

        $langDir = lang_path();
        if (! is_dir($langDir)) {
            mkdir($langDir, 0777, true);
        }

        $filePath = lang_path('stats-test.json');
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:stats')
            ->assertSuccessful();

        @unlink($filePath);
    });
});

describe('LingoSortCommand', function () {
    it('can sort translation file', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['z' => 'last', 'a' => 'first'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:sort', ['locale' => $filePath])
            ->assertSuccessful();

        $sorted = json_decode(file_get_contents($filePath), true);
        expect(array_keys($sorted)[0])->toBe('a');

        cleanupTempDir($tempDir);
    });

    it('can sort in descending order', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['a' => 'first', 'z' => 'last'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:sort', ['locale' => $filePath, '--desc' => true])
            ->assertSuccessful();

        $sorted = json_decode(file_get_contents($filePath), true);
        expect(array_keys($sorted)[0])->toBe('z');

        cleanupTempDir($tempDir);
    });

    it('uses app locale when locale argument is omitted', function () {
        config(['app.locale' => 'sort-test']);

        $langDir = lang_path();
        if (! is_dir($langDir)) {
            mkdir($langDir, 0777, true);
        }

        $filePath = lang_path('sort-test.json');
        file_put_contents($filePath, json_encode(['z' => 'last', 'a' => 'first'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:sort')
            ->assertSuccessful();

        $sorted = json_decode(file_get_contents($filePath), true);
        expect(array_keys($sorted)[0])->toBe('a');

        @unlink($filePath);
    });
});

describe('LingoSyncCommand', function () {
    it('can sync translation file with source directory', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir . '/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir . '/test.php', "<?php echo __('Hello'); echo __('World');");

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => [$srcDir]])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('can add missing keys', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir . '/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir . '/test.php', "<?php echo __('Hello'); echo __('NewKey');");

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => [$srcDir], '--add' => true])
            ->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->toHaveKey('NewKey');

        cleanupTempDir($tempDir);
    });

    it('can remove unused keys', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir . '/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'Unused' => 'Not Used'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir . '/test.php', "<?php echo __('Hello');");

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => [$srcDir], '--remove' => true])
            ->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->not->toHaveKey('Unused');
        expect($updated)->toHaveKey('Hello');

        cleanupTempDir($tempDir);
    });

    it('supports dry-run mode', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir . '/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir . '/test.php', "<?php echo __('Hello'); echo __('NewKey');");

        $this->artisan('lingo:sync', [
            'locale' => $filePath,
            '--path' => [$srcDir],
            '--add' => true,
            '--dry-run' => true,
        ])->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->not->toHaveKey('NewKey');

        cleanupTempDir($tempDir);
    });

    it('uses app locale when locale argument is omitted', function () {
        config(['app.locale' => 'sync-test']);

        $langDir = lang_path();
        if (! is_dir($langDir)) {
            mkdir($langDir, 0777, true);
        }

        $filePath = lang_path('sync-test.json');
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));

        // Uses default views path which may be empty, but should succeed
        $this->artisan('lingo:sync')
            ->assertSuccessful();

        @unlink($filePath);
    });

    it('warns gracefully with non-existent path', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));

        // With only non-existent paths, command still succeeds but shows warning
        // (no keys found in scanned directories)
        $this->artisan('lingo:sync', [
            'locale' => $filePath,
            '--path' => ['/non/existent/directory'],
        ])->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('can use add and remove together', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir . '/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode([
            'Hello' => 'Halo',
            'Unused' => 'Not Used',
        ], JSON_PRETTY_PRINT));
        file_put_contents($srcDir . '/test.php', "<?php echo __('Hello'); echo __('NewKey');");

        $this->artisan('lingo:sync', [
            'locale' => $filePath,
            '--path' => [$srcDir],
            '--add' => true,
            '--remove' => true,
        ])->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->toHaveKey('Hello');
        expect($updated)->toHaveKey('NewKey');
        expect($updated)->not->toHaveKey('Unused');

        cleanupTempDir($tempDir);
    });

    it('can scan multiple paths', function () {
        $tempDir = createTempTestDir();
        $srcDir1 = $tempDir . '/views';
        $srcDir2 = $tempDir . '/filament';
        mkdir($srcDir1, 0777, true);
        mkdir($srcDir2, 0777, true);

        $filePath = $tempDir . '/test.json';
        file_put_contents($filePath, json_encode(['Existing' => 'Ada'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir1 . '/test.blade.php', "<?php echo __('ViewKey');");
        file_put_contents($srcDir2 . '/Resource.php', "<?php echo __('FilamentKey');");

        $this->artisan('lingo:sync', [
            'locale' => $filePath,
            '--path' => [$srcDir1, $srcDir2],
            '--add' => true,
            '--remove' => true,
        ])->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->toHaveKey('ViewKey');
        expect($updated)->toHaveKey('FilamentKey');
        expect($updated)->not->toHaveKey('Existing'); // Removed as not in any source

        cleanupTempDir($tempDir);
    });
});
