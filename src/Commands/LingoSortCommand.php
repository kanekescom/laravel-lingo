<?php

namespace Kanekescom\Lingo\Commands;

use Illuminate\Console\Command;
use Kanekescom\Lingo\Commands\Concerns\ResolvesTranslationFile;
use Kanekescom\Lingo\Lingo;

/**
 * Sort translation file keys alphabetically.
 */
class LingoSortCommand extends Command
{
    use ResolvesTranslationFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'lingo:sort
                        {locale : Locale code (e.g., id, en) or full path to JSON file}
                        {--desc : Sort in descending order (Z-A)}';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Sort translation file keys alphabetically';

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

        $ascending = ! $this->option('desc');
        $sorted = Lingo::sortKeys($data['translations'], $ascending);

        Lingo::save($data['file'], $sorted, false); // Already sorted

        $direction = $ascending ? 'A-Z' : 'Z-A';
        $this->components->info("✓ File sorted ({$direction}) and saved: {$data['file']}");
        $this->newLine();

        return self::SUCCESS;
    }
}
