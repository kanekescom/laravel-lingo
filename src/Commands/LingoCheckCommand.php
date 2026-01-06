<?php

namespace Kanekescom\Lingo\Commands;

use Illuminate\Console\Command;
use Kanekescom\Lingo\Commands\Concerns\ResolvesTranslationFile;
use Kanekescom\Lingo\Lingo;

/**
 * Check translation file for issues.
 */
class LingoCheckCommand extends Command
{
    use ResolvesTranslationFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'lingo:check
                        {locale? : Locale code (e.g., id, en) or full path to JSON file. Defaults to config(app.locale)}';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Check translation file for duplicates and untranslated items';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = $this->loadTranslationFile($this->argument('locale'));

        if ($data === null) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Checking: {$data['file']}");
        $this->newLine();

        $hasIssues = false;

        // Check duplicates
        $hasIssues = $this->checkDuplicates($data['content']) || $hasIssues;

        // Check untranslated
        $hasIssues = $this->checkUntranslated($data['translations']) || $hasIssues;

        if (! $hasIssues) {
            $this->components->info('✓ No issues found!');
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Check for duplicate keys.
     */
    protected function checkDuplicates(string $jsonContent): bool
    {
        $duplicates = Lingo::duplicates($jsonContent);

        if (empty($duplicates)) {
            $this->components->info('✓ No duplicate keys found');
            $this->newLine();

            return false;
        }

        $this->components->error('✗ Duplicate keys found:');
        foreach ($duplicates as $key => $count) {
            $this->components->bulletList(["<fg=red>{$key}</> (appears {$count} times)"]);
        }

        $this->line('<fg=gray>Tip: Use lingo:clean to fix issues</>');
        $this->newLine();

        return true;
    }

    /**
     * Check for untranslated items.
     */
    protected function checkUntranslated(array $translations): bool
    {
        $untranslated = Lingo::untranslated($translations);

        if (empty($untranslated)) {
            $this->components->info('✓ All items are translated');
            $this->newLine();

            return false;
        }

        $count = count($untranslated);
        $this->components->warn("⚠ {$count} untranslated items (key = value):");

        $shown = array_slice($untranslated, 0, 10, true);
        foreach ($shown as $key => $value) {
            $this->line('  <fg=yellow>•</> '.$this->truncate($key));
        }

        if ($count > 10) {
            $this->line('  <fg=gray>... and '.($count - 10).' more</>');
        }

        $this->newLine();

        return true;
    }
}
