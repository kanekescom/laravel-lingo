<?php

/**
 * Helper to create a temp directory for testing.
 */
function createTempTestDir(): string
{
    $tempDir = sys_get_temp_dir().'/lingo-cmd-'.getmypid().'-'.uniqid();
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

    $files = glob($dir.'/*');
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
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'World' => 'World'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:check', ['locale' => $filePath])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('reports error for non-existent file', function () {
        $this->artisan('lingo:check', ['locale' => '/non/existent/file.json'])
            ->assertFailed();
    });

    it('can fix duplicates with --fix option', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir.'/test.json';
        // Create file with duplicate keys (simulated - PHP will keep last)
        file_put_contents($filePath, '{"key1": "value1", "key2": "value2", "key1": "duplicate"}');

        $this->artisan('lingo:check', ['locale' => $filePath, '--fix' => true])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('reports error for invalid JSON file', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir.'/invalid.json';
        file_put_contents($filePath, 'not valid json {{{');

        $this->artisan('lingo:check', ['locale' => $filePath])
            ->assertFailed();

        cleanupTempDir($tempDir);
    });
});

describe('LingoStatsCommand', function () {
    it('can show translation statistics', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'World' => 'World'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:stats', ['locale' => $filePath])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('can show detailed statistics', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'World' => 'World'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:stats', ['locale' => $filePath, '--detailed' => true])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });
});

describe('LingoSortCommand', function () {
    it('can sort translation file', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['z' => 'last', 'a' => 'first'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:sort', ['locale' => $filePath])
            ->assertSuccessful();

        $sorted = json_decode(file_get_contents($filePath), true);
        expect(array_keys($sorted)[0])->toBe('a');

        cleanupTempDir($tempDir);
    });

    it('can sort in descending order', function () {
        $tempDir = createTempTestDir();
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['a' => 'first', 'z' => 'last'], JSON_PRETTY_PRINT));

        $this->artisan('lingo:sort', ['locale' => $filePath, '--desc' => true])
            ->assertSuccessful();

        $sorted = json_decode(file_get_contents($filePath), true);
        expect(array_keys($sorted)[0])->toBe('z');

        cleanupTempDir($tempDir);
    });
});

describe('LingoSyncCommand', function () {
    it('can sync translation file with source directory', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir.'/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir.'/test.php', "<?php echo __('Hello'); echo __('World');");

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => $srcDir])
            ->assertSuccessful();

        cleanupTempDir($tempDir);
    });

    it('can add missing keys', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir.'/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir.'/test.php', "<?php echo __('Hello'); echo __('NewKey');");

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => $srcDir, '--add' => true])
            ->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->toHaveKey('NewKey');

        cleanupTempDir($tempDir);
    });

    it('can remove unused keys', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir.'/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo', 'Unused' => 'Not Used'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir.'/test.php', "<?php echo __('Hello');");

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => $srcDir, '--remove' => true])
            ->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->not->toHaveKey('Unused');
        expect($updated)->toHaveKey('Hello');

        cleanupTempDir($tempDir);
    });

    it('supports dry-run mode', function () {
        $tempDir = createTempTestDir();
        $srcDir = $tempDir.'/src';
        mkdir($srcDir, 0777, true);

        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode(['Hello' => 'Halo'], JSON_PRETTY_PRINT));
        file_put_contents($srcDir.'/test.php', "<?php echo __('Hello'); echo __('NewKey');");

        $this->artisan('lingo:sync', [
            'locale' => $filePath,
            '--path' => $srcDir,
            '--add' => true,
            '--dry-run' => true,
        ])->assertSuccessful();

        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->not->toHaveKey('NewKey');

        cleanupTempDir($tempDir);
    });
});
