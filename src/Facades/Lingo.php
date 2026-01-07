<?php

namespace Kanekescom\Lingo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Lingo Facade - Provides translation management utilities.
 *
 * Chainable methods (returns LingoBuilder):
 *
 * @method static \Kanekescom\Lingo\LingoBuilder make(array $translations = [])
 * @method static \Kanekescom\Lingo\LingoBuilder locale(string $locale)
 * @method static \Kanekescom\Lingo\LingoBuilder fromFile(string $filePath)
 *
 * Static utility methods:
 * @method static array|null load(string $filePath)
 * @method static array duplicates(string $jsonContent)
 * @method static bool hasDuplicates(string $jsonContent)
 * @method static array removeDuplicates(string $jsonContent)
 * @method static array sortKeys(array $translations, bool $ascending = true)
 * @method static array untranslated(array $translations)
 * @method static bool hasUntranslated(array $translations)
 * @method static array translated(array $translations)
 * @method static array stats(array $translations)
 * @method static array clean(array $translations)
 * @method static string toJson(array $translations, bool $sortKeys = true)
 * @method static array extractKeys(string $content)
 * @method static array missing(array $translations, array $keys)
 * @method static bool hasMissing(array $translations, array $keys)
 * @method static array addMissing(array $translations, array $keys)
 * @method static array unused(array $translations, array $usedKeys)
 * @method static bool hasUnused(array $translations, array $usedKeys)
 * @method static array removeUnused(array $translations, array $usedKeys)
 * @method static bool save(string $path, array $translations, bool $sort = true)
 * @method static string resolvePath(string $path)
 * @method static array scan(string $path, array $extensions = ['php'])
 * @method static array scanDirectory(string $path, array $extensions = ['php', 'blade.php'])
 *
 * @see \Kanekescom\Lingo\Lingo
 */
class Lingo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kanekescom\Lingo\Lingo::class;
    }
}
