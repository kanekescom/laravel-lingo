<?php

namespace Kanekescom\Lingo;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Kanekescom\Lingo\Commands\LingoCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_lingo_table')
            ->hasCommand(LingoCommand::class);
    }
}
