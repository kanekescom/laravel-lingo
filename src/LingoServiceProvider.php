<?php

namespace Kanekescom\Lingo;

use Kanekescom\Lingo\Commands\LingoCheckCommand;
use Kanekescom\Lingo\Commands\LingoCleanCommand;
use Kanekescom\Lingo\Commands\LingoSortCommand;
use Kanekescom\Lingo\Commands\LingoStatsCommand;
use Kanekescom\Lingo\Commands\LingoSyncCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LingoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-lingo')
            ->hasCommands([
                LingoCheckCommand::class,
                LingoCleanCommand::class,
                LingoSortCommand::class,
                LingoStatsCommand::class,
                LingoSyncCommand::class,
            ]);
    }
}
