<?php

namespace Kanekescom\Lingo\Commands;

use Illuminate\Console\Command;
use Kanekescom\Lingo\Commands\Concerns\ResolvesTranslationFile;
use Kanekescom\Lingo\Lingo;

/**
 * Clean translation file (remove duplicates, empty values, and sort keys).
 */
class LingoCleanCommand extends Command
{
    use ResolvesTranslationFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'lingo:clean
                        {locale? : Locale code (e.g., id, en) or full path to JSON file. Defaults to config(app.locale)}
                        {--keep-empty : Keep empty values instead of removing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Clean translation file (remove duplicates, empty values, and sort keys)';

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
        $this->components->info("Cleaning: {$data['file']}");
        $this->newLine();

        $original = $data['translations'];
        $originalCount = count($original);

        // Check and remove duplicates from raw content
        $duplicates = Lingo::duplicates($data['content']);
        $translations = Lingo::removeDuplicates($data['content']);

        // Remove empty values (unless --keep-empty)
        $emptyCount = 0;
        if (! $this->option('keep-empty')) {
            $beforeRemoveEmpty = count($translations);
            $translations = array_filter($translations, fn ($value) => $value !== '');
            $emptyCount = $beforeRemoveEmpty - count($translations);
        }

        // Sort keys
        $translations = Lingo::sortKeys($translations);

        // Save
        Lingo::save($data['file'], $translations, false); // Already sorted

        // Report
        $this->components->twoColumnDetail('Original keys', (string) $originalCount);
        $this->components->twoColumnDetail('Duplicates removed', '<fg=yellow>'.count($duplicates).'</>');
        $this->components->twoColumnDetail('Empty values removed', '<fg=yellow>'.$emptyCount.'</>');
        $this->components->twoColumnDetail('Final keys', '<fg=green>'.count($translations).'</>');
        $this->newLine();

        $this->components->info("✓ File cleaned and saved: {$data['file']}");
        $this->newLine();

        return self::SUCCESS;
    }
}
