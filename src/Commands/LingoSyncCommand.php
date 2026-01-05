<?php

namespace Kanekescom\Lingo\Commands;

use Illuminate\Console\Command;
use Kanekescom\Lingo\Commands\Concerns\ResolvesTranslationFile;
use Kanekescom\Lingo\Lingo;

/**
 * Sync translation file with source files.
 */
class LingoSyncCommand extends Command
{
    use ResolvesTranslationFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'lingo:sync
                        {locale : Locale code (e.g., id, en) or full path to JSON file}
                        {--path=resources/views : Directory to scan for translation keys}
                        {--add : Add missing keys to translation file}
                        {--remove : Remove unused keys from translation file}
                        {--dry-run : Show what would be changed without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Sync translation file with source files (find missing/unused keys)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = $this->loadTranslationFile($this->argument('locale'));

        if ($data === null) {
            return self::FAILURE;
        }

        $scanPath = $this->resolveScanPath($this->option('path'));

        if (! is_dir($scanPath)) {
            $this->error("Directory not found: {$scanPath}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Syncing: {$data['file']}");
        $this->components->info("Scanning: {$scanPath}");
        $this->newLine();

        // Scan for translation keys
        $foundKeys = Lingo::scanDirectory($scanPath);
        $missing = Lingo::missing($data['translations'], $foundKeys);
        $unused = Lingo::unused($data['translations'], $foundKeys);

        // Show summary
        $this->components->twoColumnDetail('Keys found in source', (string) count($foundKeys));
        $this->components->twoColumnDetail('Keys in translation file', (string) count($data['translations']));
        $this->components->twoColumnDetail('Missing keys', '<fg=yellow>'.count($missing).'</>');
        $this->components->twoColumnDetail('Unused keys', '<fg=cyan>'.count($unused).'</>');
        $this->newLine();

        if (empty($missing) && empty($unused)) {
            $this->components->info('✓ All translation keys are in sync!');
            $this->newLine();

            return self::SUCCESS;
        }

        $translations = $data['translations'];
        $isDryRun = $this->option('dry-run');

        // Handle missing keys
        if (! empty($missing)) {
            $this->showMissingKeys($missing);

            if ($this->option('add') && ! $isDryRun) {
                $translations = Lingo::addMissing($translations, $foundKeys);
                $this->components->info('✓ Added '.count($missing).' missing key(s)');
                $this->newLine();
            } elseif ($this->option('add') && $isDryRun) {
                $this->line('<fg=gray>[dry-run] Would add '.count($missing).' key(s)</>');
                $this->newLine();
            } else {
                $this->line('<fg=gray>Tip: Use --add to add missing keys</>');
                $this->newLine();
            }
        }

        // Handle unused keys
        if (! empty($unused)) {
            $this->showUnusedKeys($unused);

            if ($this->option('remove') && ! $isDryRun) {
                $translations = Lingo::removeUnused($translations, $foundKeys);
                $this->components->info('✓ Removed '.count($unused).' unused key(s)');
                $this->newLine();
            } elseif ($this->option('remove') && $isDryRun) {
                $this->line('<fg=gray>[dry-run] Would remove '.count($unused).' key(s)</>');
                $this->newLine();
            } else {
                $this->line('<fg=gray>Tip: Use --remove to remove unused keys</>');
                $this->newLine();
            }
        }

        // Save if changes were made and not dry-run
        if (! $isDryRun && ($this->option('add') || $this->option('remove'))) {
            Lingo::save($data['file'], $translations, true);
            $this->components->info("✓ File saved: {$data['file']}");
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Resolve scan path.
     */
    protected function resolveScanPath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Z]:/i', $path)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * Show missing keys.
     */
    protected function showMissingKeys(array $missing): void
    {
        $this->components->warn('⚠ Missing translation keys:');
        $shown = array_slice($missing, 0, 15);

        foreach ($shown as $key) {
            $this->line('  <fg=yellow>•</> '.$this->truncate($key));
        }

        if (count($missing) > 15) {
            $this->line('  <fg=gray>... and '.(count($missing) - 15).' more</>');
        }

        $this->newLine();
    }

    /**
     * Show unused keys.
     */
    protected function showUnusedKeys(array $unused): void
    {
        $this->components->warn('⚠ Unused translation keys (not in source):');
        $shown = array_slice($unused, 0, 15);

        foreach ($shown as $key) {
            $this->line('  <fg=cyan>•</> '.$this->truncate($key));
        }

        if (count($unused) > 15) {
            $this->line('  <fg=gray>... and '.(count($unused) - 15).' more</>');
        }

        $this->newLine();
    }
}
