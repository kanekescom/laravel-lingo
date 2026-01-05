<?php

describe('LingoCheckCommand', function () {
    it('can check translation file for issues', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        @mkdir($tempDir, 0777, true);

        // Create test file with untranslated items
        $translations = ['Hello' => 'Halo', 'World' => 'World'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        $this->artisan('lingo:check', ['locale' => $filePath])
            ->assertSuccessful();

        // Cleanup
        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('reports error for non-existent file', function () {
        $this->artisan('lingo:check', ['locale' => '/non/existent/file.json'])
            ->assertFailed();
    });
});

describe('LingoStatsCommand', function () {
    it('can show translation statistics', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        @mkdir($tempDir, 0777, true);

        $translations = ['Hello' => 'Halo', 'World' => 'World'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        $this->artisan('lingo:stats', ['locale' => $filePath])
            ->assertSuccessful();

        // Cleanup
        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('can show detailed statistics', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        @mkdir($tempDir, 0777, true);

        $translations = ['Hello' => 'Halo', 'World' => 'World'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        $this->artisan('lingo:stats', ['locale' => $filePath, '--detailed' => true])
            ->assertSuccessful();

        // Cleanup
        @unlink($filePath);
        @rmdir($tempDir);
    });
});

describe('LingoSortCommand', function () {
    it('can sort translation file', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        @mkdir($tempDir, 0777, true);

        $translations = ['z' => 'last', 'a' => 'first'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        $this->artisan('lingo:sort', ['locale' => $filePath])
            ->assertSuccessful();

        // Verify sorted
        $sorted = json_decode(file_get_contents($filePath), true);
        $keys = array_keys($sorted);
        expect($keys[0])->toBe('a');

        // Cleanup
        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('can sort in descending order', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        @mkdir($tempDir, 0777, true);

        $translations = ['a' => 'first', 'z' => 'last'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        $this->artisan('lingo:sort', ['locale' => $filePath, '--desc' => true])
            ->assertSuccessful();

        // Verify sorted descending
        $sorted = json_decode(file_get_contents($filePath), true);
        $keys = array_keys($sorted);
        expect($keys[0])->toBe('z');

        // Cleanup
        @unlink($filePath);
        @rmdir($tempDir);
    });
});

describe('LingoSyncCommand', function () {
    it('can sync translation file with source directory', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        $srcDir = $tempDir.'/src';
        @mkdir($srcDir, 0777, true);

        // Create translation file
        $translations = ['Hello' => 'Halo'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        // Create source file with translation keys
        $phpContent = "<?php echo __('Hello'); echo __('World');";
        file_put_contents($srcDir.'/test.php', $phpContent);

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => $srcDir])
            ->assertSuccessful();

        // Cleanup
        @unlink($srcDir.'/test.php');
        @rmdir($srcDir);
        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('can add missing keys', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        $srcDir = $tempDir.'/src';
        @mkdir($srcDir, 0777, true);

        // Create translation file
        $translations = ['Hello' => 'Halo'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        // Create source file with new key
        $phpContent = "<?php echo __('Hello'); echo __('NewKey');";
        file_put_contents($srcDir.'/test.php', $phpContent);

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => $srcDir, '--add' => true])
            ->assertSuccessful();

        // Verify key was added
        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->toHaveKey('NewKey');

        // Cleanup
        @unlink($srcDir.'/test.php');
        @rmdir($srcDir);
        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('can remove unused keys', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        $srcDir = $tempDir.'/src';
        @mkdir($srcDir, 0777, true);

        // Create translation file with unused key
        $translations = ['Hello' => 'Halo', 'Unused' => 'Not Used'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        // Create source file that only uses Hello
        $phpContent = "<?php echo __('Hello');";
        file_put_contents($srcDir.'/test.php', $phpContent);

        $this->artisan('lingo:sync', ['locale' => $filePath, '--path' => $srcDir, '--remove' => true])
            ->assertSuccessful();

        // Verify unused key was removed
        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->not->toHaveKey('Unused');
        expect($updated)->toHaveKey('Hello');

        // Cleanup
        @unlink($srcDir.'/test.php');
        @rmdir($srcDir);
        @unlink($filePath);
        @rmdir($tempDir);
    });

    it('supports dry-run mode', function () {
        $tempDir = sys_get_temp_dir().'/lingo-cmd-test-'.uniqid();
        $srcDir = $tempDir.'/src';
        @mkdir($srcDir, 0777, true);

        // Create translation file
        $translations = ['Hello' => 'Halo'];
        $filePath = $tempDir.'/test.json';
        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        // Create source file with new key
        $phpContent = "<?php echo __('Hello'); echo __('NewKey');";
        file_put_contents($srcDir.'/test.php', $phpContent);

        $this->artisan('lingo:sync', [
            'locale' => $filePath,
            '--path' => $srcDir,
            '--add' => true,
            '--dry-run' => true,
        ])->assertSuccessful();

        // Verify key was NOT added (dry-run)
        $updated = json_decode(file_get_contents($filePath), true);
        expect($updated)->not->toHaveKey('NewKey');

        // Cleanup
        @unlink($srcDir.'/test.php');
        @rmdir($srcDir);
        @unlink($filePath);
        @rmdir($tempDir);
    });
});
