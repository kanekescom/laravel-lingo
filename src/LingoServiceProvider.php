<?php

namespace Kanekescom\Lingo;

use Kanekescom\Lingo\Commands\LingoCommand;
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
            ->hasCommand(LingoCommand::class);
    }
}
