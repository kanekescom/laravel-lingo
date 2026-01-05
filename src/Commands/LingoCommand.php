<?php

namespace Kanekescom\Lingo\Commands;

use Illuminate\Console\Command;
use Kanekescom\Lingo\Lingo;

/**
 * Artisan command for translation file utilities.
 */
class LingoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'lingo:manage
                        {locale : Locale code (e.g., id, en) or full path to JSON file}
                        {--check : Check for issues (duplicates, untranslated)}
                        {--sort : Sort keys alphabetically and save}
                        {--remove-duplicates : Remove duplicate keys from raw JSON and save}
                        {--stats : Show translation statistics}
                        {--scan= : Scan directory for missing translation keys (e.g., resources/views)}
                        {--add-missing : Add missing keys found from --scan to translation file}
                        {--remove-unused : Remove unused keys (not found in --scan directory) from translation file}';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Manage and analyze translation files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locale = $this->argument('locale');
        $file = $this->resolveFilePath($locale);

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            $this->newLine();
            $this->line('<fg=gray>Tip: You can specify locale (e.g., id) or full path (e.g., lang/id.json)</>');

            return self::FAILURE;
        }

        $jsonContent = file_get_contents($file);
        $translations = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: ' . json_last_error_msg());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Analyzing: {$file}");
        $this->newLine();

        // Always show stats
        $this->showStats($translations);

        // Check for issues
        if ($this->option('check')) {
            $this->checkDuplicates($jsonContent);
            $this->checkUntranslated($translations);
        }

        // Remove duplicates
        if ($this->option('remove-duplicates')) {
            $this->removeDuplicatesAndSave($file, $jsonContent);
        }

        // Sort and save
        if ($this->option('sort')) {
            // Reload file if duplicates were removed
            if ($this->option('remove-duplicates')) {
                $jsonContent = file_get_contents($file);
                $translations = json_decode($jsonContent, true);
            }
            $this->sortAndSave($file, $translations);
        }

        // Scan for missing keys
        if ($this->option('scan')) {
            // Reload file if changes were made
            if ($this->option('remove-duplicates') || $this->option('sort')) {
                $jsonContent = file_get_contents($file);
                $translations = json_decode($jsonContent, true);
            }
            $translations = $this->scanAndReport($file, $translations);
        }

        // Show detailed stats
        if ($this->option('stats')) {
            // Reload file if changes were made
            if ($this->option('remove-duplicates') || $this->option('sort') || $this->option('add-missing')) {
                $jsonContent = file_get_contents($file);
                $translations = json_decode($jsonContent, true);
            }
            $this->showDetailedStats($translations);
        }

        return self::SUCCESS;
    }

    /**
     * Resolve file path from locale or path.
     */
    protected function resolveFilePath(string $locale): string
    {
        // If it's already a path (contains / or \ or ends with .json)
        if (str_contains($locale, '/') || str_contains($locale, '\\') || str_ends_with($locale, '.json')) {
            return $locale;
        }

        // Try common Laravel paths
        $possiblePaths = [
            base_path("lang/{$locale}.json"),
            lang_path("{$locale}.json"),
            resource_path("lang/{$locale}.json"),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Default to lang/{locale}.json
        return base_path("lang/{$locale}.json");
    }

    /**
     * Show basic statistics.
     */
    protected function showStats(array $translations): void
    {
        $stats = Lingo::stats($translations);

        $this->components->twoColumnDetail('Total keys', (string) $stats['total']);
        $this->components->twoColumnDetail('Translated', "<fg=green>{$stats['translated']}</>");
        $this->components->twoColumnDetail('Untranslated', "<fg=yellow>{$stats['untranslated']}</>");
        $this->components->twoColumnDetail('Progress', "<fg=cyan>{$stats['percentage']}%</>");
        $this->newLine();
    }

    /**
     * Check for duplicate keys.
     */
    protected function checkDuplicates(string $jsonContent): void
    {
        $duplicates = Lingo::duplicates($jsonContent);

        if (empty($duplicates)) {
            $this->components->info('✓ No duplicate keys found');
        } else {
            $this->components->error('✗ Duplicate keys found:');
            foreach ($duplicates as $key => $count) {
                $this->components->bulletList(["<fg=red>{$key}</> (appears {$count} times)"]);
            }
            $this->line('<fg=gray>Tip: Use --remove-duplicates to fix</>');
        }
        $this->newLine();
    }

    /**
     * Check for untranslated items.
     */
    protected function checkUntranslated(array $translations): void
    {
        $untranslated = Lingo::untranslated($translations);

        if (empty($untranslated)) {
            $this->components->info('✓ All items are translated');
        } else {
            $count = count($untranslated);
            $this->components->warn("⚠ {$count} untranslated items (key = value):");

            // Show first 10 only
            $shown = array_slice($untranslated, 0, 10, true);
            foreach ($shown as $key => $value) {
                $shortKey = strlen($key) > 60 ? substr($key, 0, 60) . '...' : $key;
                $this->line("  <fg=yellow>•</> {$shortKey}");
            }

            if ($count > 10) {
                $remaining = $count - 10;
                $this->line("  <fg=gray>... and {$remaining} more</>");
            }
        }
        $this->newLine();
    }

    /**
     * Remove duplicate keys and save.
     */
    protected function removeDuplicatesAndSave(string $file, string $jsonContent): void
    {
        if (! Lingo::hasDuplicates($jsonContent)) {
            $this->components->info('✓ No duplicates to remove');
            $this->newLine();

            return;
        }

        $duplicates = Lingo::duplicates($jsonContent);
        $translations = Lingo::removeDuplicates($jsonContent);
        Lingo::save($file, $translations, false); // Don't sort

        $count = count($duplicates);
        $this->components->info("✓ Removed {$count} duplicate key(s) (kept last occurrence)");
        $this->newLine();
    }

    /**
     * Sort keys and save file.
     */
    protected function sortAndSave(string $file, array $translations): void
    {
        Lingo::save($file, $translations, true); // Sort keys

        $this->components->info("✓ File sorted and saved: {$file}");
        $this->newLine();
    }

    /**
     * Show detailed statistics.
     */
    protected function showDetailedStats(array $translations): void
    {
        $untranslated = Lingo::untranslated($translations);
        $translated = Lingo::translated($translations);

        $this->components->info('Detailed Statistics:');
        $this->newLine();

        // Sample translated
        if (! empty($translated)) {
            $this->line('<fg=green>Sample translated items:</>');
            $sample = array_slice($translated, 0, 5, true);
            foreach ($sample as $key => $value) {
                $shortKey = strlen($key) > 30 ? substr($key, 0, 30) . '...' : $key;
                $shortVal = strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
                $this->line("  <fg=gray>{$shortKey}</> → <fg=green>{$shortVal}</>");
            }
            $this->newLine();
        }

        // Sample untranslated
        if (! empty($untranslated)) {
            $this->line('<fg=yellow>Sample untranslated items:</>');
            $sample = array_slice($untranslated, 0, 5, true);
            foreach ($sample as $key => $value) {
                $shortKey = strlen($key) > 50 ? substr($key, 0, 50) . '...' : $key;
                $this->line("  <fg=yellow>{$shortKey}</>");
            }
            $this->newLine();
        }
    }

    /**
     * Scan directory for missing translation keys and optionally add them.
     */
    protected function scanAndReport(string $file, array $translations): array
    {
        $scanPath = $this->option('scan');

        // Resolve relative paths
        if (! str_starts_with($scanPath, '/') && ! preg_match('/^[A-Z]:/i', $scanPath)) {
            $scanPath = base_path($scanPath);
        }

        if (! is_dir($scanPath)) {
            $this->components->error("Directory not found: {$scanPath}");
            $this->newLine();

            return $translations;
        }

        $this->components->info("Scanning: {$scanPath}");
        $this->newLine();

        // Scan for translation keys
        $foundKeys = Lingo::scanDirectory($scanPath);
        $missing = Lingo::missing($translations, $foundKeys);
        $unused = Lingo::unused($translations, $foundKeys);

        $this->components->twoColumnDetail('Keys found in source', (string) count($foundKeys));
        $this->components->twoColumnDetail('Keys in translation file', (string) count($translations));
        $this->components->twoColumnDetail('Missing keys', '<fg=yellow>' . count($missing) . '</>');
        $this->components->twoColumnDetail('Unused keys', '<fg=cyan>' . count($unused) . '</>');
        $this->newLine();

        if (empty($missing) && empty($unused)) {
            $this->components->info('✓ All translation keys are in sync');
            $this->newLine();

            return $translations;
        }

        // Show missing keys
        if (! empty($missing)) {
            $this->components->warn('⚠ Missing translation keys:');
            $shown = array_slice($missing, 0, 15);
            foreach ($shown as $key) {
                $shortKey = strlen($key) > 60 ? substr($key, 0, 60) . '...' : $key;
                $this->line("  <fg=yellow>•</> {$shortKey}");
            }

            if (count($missing) > 15) {
                $remaining = count($missing) - 15;
                $this->line("  <fg=gray>... and {$remaining} more</>");
            }
            $this->newLine();

            // Add missing keys if requested
            if ($this->option('add-missing')) {
                $translations = Lingo::addMissing($translations, $foundKeys);
                Lingo::save($file, $translations, true);

                $this->components->info('✓ Added ' . count($missing) . ' missing key(s) to translation file');
                $this->newLine();
            } else {
                $this->line('<fg=gray>Tip: Use --add-missing to add missing keys</>');
                $this->newLine();
            }
        }

        // Show unused keys
        if (! empty($unused)) {
            $this->components->warn('⚠ Unused translation keys (not in source):');
            $shown = array_slice($unused, 0, 15);
            foreach ($shown as $key) {
                $shortKey = strlen($key) > 60 ? substr($key, 0, 60) . '...' : $key;
                $this->line("  <fg=cyan>•</> {$shortKey}");
            }

            if (count($unused) > 15) {
                $remaining = count($unused) - 15;
                $this->line("  <fg=gray>... and {$remaining} more</>");
            }
            $this->newLine();

            // Remove unused keys if requested
            if ($this->option('remove-unused')) {
                $translations = Lingo::removeUnused($translations, $foundKeys);
                Lingo::save($file, $translations, true);

                $this->components->info('✓ Removed ' . count($unused) . ' unused key(s) from translation file');
                $this->newLine();
            } else {
                $this->line('<fg=gray>Tip: Use --remove-unused to remove unused keys</>');
                $this->newLine();
            }
        }

        return $translations;
    }
}
