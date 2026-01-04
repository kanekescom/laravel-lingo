<?php

namespace Kanekescom\Lingo\Commands;

use Illuminate\Console\Command;

class LingoCommand extends Command
{
    public $signature = 'laravel-lingo';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
