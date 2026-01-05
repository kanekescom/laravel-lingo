<?php

use Kanekescom\Lingo\Lingo;
use Kanekescom\Lingo\LingoBuilder;

/*
|--------------------------------------------------------------------------
| Translation Helper Functions
|--------------------------------------------------------------------------
|
| These functions are wrappers around the Lingo class for convenience.
| For more features, use Kanekescom\Lingo\Lingo directly.
|
*/

if (! function_exists('lingo')) {
    /**
     * Create a new LingoBuilder instance for chainable operations.
     *
     * Similar to Laravel's collect() helper, this provides a chainable
     * interface for working with translation arrays.
     *
     * @param  array<string, string>  $translations
     * @return LingoBuilder
     *
     * @example
     * lingo()->setLocale('id')->syncWith()->save();
     * lingo()->setLocale('id')->stats();
     * lingo(['Hello' => 'Halo'])->sortKeys()->toJson();
     */
    function lingo(array $translations = []): LingoBuilder
    {
        return Lingo::make($translations);
    }
}
