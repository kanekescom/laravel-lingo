<?php

namespace Kanekescom\Lingo\Commands;

use Illuminate\Console\Command;
use Kanekescom\Lingo\Commands\Concerns\ResolvesTranslationFile;
use Kanekescom\Lingo\Lingo;

/**
 * Show translation file statistics.
 */
class LingoStatsCommand extends Command
{
    use ResolvesTranslationFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'lingo:stats
                        {locale : Locale code (e.g., id, en) or full path to JSON file}
                        {--detailed : Show sample translated and untranslated items}';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Show translation file statistics';

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
        $this->components->info("Statistics: {$data['file']}");
        $this->newLine();

        $this->showStats($data['translations']);

        if ($this->option('detailed')) {
            $this->showDetailedStats($data['translations']);
        }

        return self::SUCCESS;
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
     * Show detailed statistics with samples.
     */
    protected function showDetailedStats(array $translations): void
    {
        $translated = Lingo::translated($translations);
        $untranslated = Lingo::untranslated($translations);

        // Sample translated
        if (! empty($translated)) {
            $this->line('<fg=green>Sample translated items:</>');
            $sample = array_slice($translated, 0, 5, true);

            foreach ($sample as $key => $value) {
                $shortKey = $this->truncate($key, 30);
                $shortVal = $this->truncate($value, 30);
                $this->line("  <fg=gray>{$shortKey}</> → <fg=green>{$shortVal}</>");
            }

            $this->newLine();
        }

        // Sample untranslated
        if (! empty($untranslated)) {
            $this->line('<fg=yellow>Sample untranslated items:</>');
            $sample = array_slice($untranslated, 0, 5, true);

            foreach ($sample as $key => $value) {
                $this->line('  <fg=yellow>'.$this->truncate($key, 50).'</>');
            }

            $this->newLine();
        }
    }
}
