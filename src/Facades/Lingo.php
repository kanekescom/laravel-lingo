<?php

namespace Kanekescom\Lingo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kanekescom\Lingo\Lingo
 */
class Lingo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kanekescom\Lingo\Lingo::class;
    }
}
