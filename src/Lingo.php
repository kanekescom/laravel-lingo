<?php

namespace Kanekescom\Lingo;

/**
 * Translation utility class.
 *
 * Provides static methods for working with translation files,
 * particularly JSON translation files like lang/id.json.
 */
class Lingo
{
    /**
     * Create a new LingoBuilder instance for chainable operations.
     *
     * @param  array<string, string>  $translations
     * @param  string|null  $locale  Optional locale code for auto-save path
     *
     * @example
     * Lingo::make($translations)
     *     ->addMissing($keys)
     *     ->sortKeys()
     *     ->save('lang/id.json');
     */
    public static function make(array $translations = [], ?string $locale = null): LingoBuilder
    {
        return LingoBuilder::make($translations, $locale);
    }

    /**
     * Load translations by locale code for chainable operations.
     *
     * Automatically resolves to lang_path('{locale}.json').
     *
     * @param  string  $locale  Locale code (e.g., 'id', 'en', 'fr')
     *
     * @example
     * Lingo::locale('id')->sortKeys()->save();
     * Lingo::locale('id')->stats();
     */
    public static function locale(string $locale): LingoBuilder
    {
        return LingoBuilder::locale($locale);
    }

    /**
     * Load translations from a JSON file for chainable operations.
     *
     * @param  string  $filePath  Path to JSON file (relative to lang_path or absolute)
     *
     * @example
     * Lingo::fromFile('id.json')->sortKeys()->save();
     * Lingo::fromFile(lang_path('id.json'))->stats();
     */
    public static function fromFile(string $filePath): LingoBuilder
    {
        return LingoBuilder::load($filePath);
    }

    /**
     * Find duplicate keys in raw JSON content.
     *
     * Since PHP arrays cannot have duplicate keys, this function
     * works with the raw JSON file content to detect duplicates
     * before they are merged by PHP.
     *
     * @param  string  $jsonContent  Raw JSON file content
     * @return array<string, int> Array of duplicate keys with their occurrence count
     */
    public static function duplicates(string $jsonContent): array
    {
        preg_match_all('/"([^"]+)"\s*:/', $jsonContent, $matches);

        $keys = $matches[1];
        $counts = array_count_values($keys);

        return array_filter($counts, fn ($count) => $count > 1);
    }

    /**
     * Check if JSON content has duplicate keys.
     *
     * @param  string  $jsonContent  Raw JSON file content
     */
    public static function hasDuplicates(string $jsonContent): bool
    {
        return count(static::duplicates($jsonContent)) > 0;
    }

    /**
     * Remove duplicates from raw JSON content by parsing and re-encoding.
     * PHP automatically keeps the last occurrence when parsing.
     *
     * @param  string  $jsonContent  Raw JSON file content
     * @return array<string, string> Clean translation array without duplicates
     */
    public static function removeDuplicates(string $jsonContent): array
    {
        return json_decode($jsonContent, true) ?? [];
    }

    /**
     * Load translation file from path.
     *
     * @param  string  $filePath  Path to JSON file
     * @return array{content: string, translations: array<string, string>}|null
     */
    public static function load(string $filePath): ?array
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $translations = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return [
            'content' => $content,
            'translations' => $translations,
        ];
    }

    /**
     * Save translation array to JSON file.
     *
     * @param  string  $filePath  Path to save JSON file
     * @param  array<string, string>  $translations
     * @param  bool  $sort  Whether to sort keys before saving
     * @return bool True if saved successfully
     */
    public static function save(string $filePath, array $translations, bool $sort = true): bool
    {
        $json = static::toJson($translations, $sort);

        return file_put_contents($filePath, $json) !== false;
    }

    /**
     * Sort translation array by keys alphabetically.
     *
     * @param  array<string, string>  $translations
     * @param  bool  $ascending  Sort ascending (A-Z) if true, descending (Z-A) if false
     * @return array<string, string>
     */
    public static function sortKeys(array $translations, bool $ascending = true): array
    {
        if ($ascending) {
            ksort($translations, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            krsort($translations, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $translations;
    }

    /**
     * Find untranslated items where key equals value.
     *
     * @param  array<string, string>  $translations
     * @return array<string, string> Array of untranslated items
     */
    public static function untranslated(array $translations): array
    {
        return array_filter($translations, fn ($value, $key) => $key === $value, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Check if translation array has any untranslated items.
     *
     * @param  array<string, string>  $translations
     */
    public static function hasUntranslated(array $translations): bool
    {
        return count(static::untranslated($translations)) > 0;
    }

    /**
     * Find translated items where key differs from value.
     *
     * @param  array<string, string>  $translations
     * @return array<string, string> Array of translated items
     */
    public static function translated(array $translations): array
    {
        return array_filter($translations, fn ($value, $key) => $key !== $value, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get translation statistics.
     *
     * @param  array<string, string>  $translations
     * @return array{total: int, translated: int, untranslated: int, percentage: float}
     */
    public static function stats(array $translations): array
    {
        $total = count($translations);
        $untranslated = count(static::untranslated($translations));
        $translated = $total - $untranslated;

        return [
            'total' => $total,
            'translated' => $translated,
            'untranslated' => $untranslated,
            'percentage' => $total > 0 ? round(($translated / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Clean and format translation array.
     *
     * Sorts keys alphabetically and removes any empty values.
     *
     * @param  array<string, string>  $translations
     * @return array<string, string>
     */
    public static function clean(array $translations): array
    {
        // Remove empty values
        $cleaned = array_filter($translations, fn ($value) => $value !== '');

        // Sort keys
        return static::sortKeys($cleaned);
    }

    /**
     * Export translation array to formatted JSON.
     *
     * @param  array<string, string>  $translations
     * @param  bool  $sortKeys  Whether to sort keys before export
     * @return string Formatted JSON string
     */
    public static function toJson(array $translations, bool $sortKeys = true): string
    {
        if ($sortKeys) {
            $translations = static::sortKeys($translations);
        }

        return json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Resolve path to absolute path within the application.
     *
     * Paths are resolved relative to base_path(). If the path is already
     * absolute and exists, it will be used directly (for backward compatibility).
     *
     * @param  string  $path  Path relative to application root (or absolute path)
     * @return string Absolute path
     */
    public static function resolvePath(string $path): string
    {
        // Check if path is already absolute (Unix or Windows)
        $isAbsolute = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path);

        if ($isAbsolute) {
            // If absolute path exists, use it directly (backward compatibility)
            if (is_file($path) || is_dir($path)) {
                return $path;
            }
        }

        // Resolve using base_path for relative paths
        return base_path($path);
    }

    /**
     * Scan path for translation keys.
     *
     * Supports both files and directories. For directories, scans recursively.
     * Paths are always resolved relative to base_path() for security.
     *
     * @param  string  $path  Path to file or directory (relative to application root)
     * @param  array<string>  $extensions  File extensions to scan (default: ['php'])
     * @return array<string> Array of unique translation keys found
     *
     * @example
     * Lingo::scan('app/Filament');                    // Scan directory
     * Lingo::scan('app/Http/Controllers/Home.php');   // Scan single file
     * Lingo::scan('resources/views');                 // Scan views directory
     */
    public static function scan(string $path, array $extensions = ['php']): array
    {
        $resolvedPath = static::resolvePath($path);

        // Handle single file
        if (is_file($resolvedPath)) {
            $content = file_get_contents($resolvedPath);

            return static::extractKeys($content);
        }

        // Handle directory
        if (is_dir($resolvedPath)) {
            return static::scanDirectory($resolvedPath, $extensions);
        }

        return [];
    }

    /**
     * Scan directory for translation keys used in files.
     *
     * Searches for patterns like __('text'), __("text"), @lang('text'), trans('text')
     *
     * @param  string  $directory  Directory to scan
     * @param  array<string>  $extensions  File extensions to scan (default: ['php', 'blade.php'])
     * @return array<string> Array of unique translation keys found
     */
    public static function scanDirectory(string $directory, array $extensions = ['php']): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $keys = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();
            $matchesExtension = false;

            foreach ($extensions as $ext) {
                if (str_ends_with($filename, '.'.$ext)) {
                    $matchesExtension = true;
                    break;
                }
            }

            if (! $matchesExtension) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $fileKeys = static::extractKeys($content);
            $keys = array_merge($keys, $fileKeys);
        }

        return array_unique($keys);
    }

    /**
     * Extract translation keys from file content.
     *
     * Matches patterns:
     * - __('text') or __("text")
     * - @lang('text') or @lang("text")
     * - trans('text') or trans("text")
     * - Lang::get('text') or Lang::get("text")
     *
     * @param  string  $content  File content
     * @return array<string> Array of translation keys found
     */
    public static function extractKeys(string $content): array
    {
        $keys = [];

        // Patterns to match translation functions
        $patterns = [
            // __('text') or __("text")
            '/__\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,|\))/',
            // @lang('text') or @lang("text")
            '/@lang\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            // trans('text') or trans("text")
            '/\btrans\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,|\))/',
            // Lang::get('text') or Lang::get("text")
            '/Lang::get\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,|\))/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return $keys;
    }

    /**
     * Find translation keys that are missing from the translation array.
     *
     * @param  array<string, string>  $translations  Existing translations
     * @param  array<string>  $keys  Keys found in source files
     * @return array<string> Array of missing keys
     */
    public static function missing(array $translations, array $keys): array
    {
        return array_values(array_filter($keys, fn ($key) => ! array_key_exists($key, $translations)));
    }

    /**
     * Check if there are missing translation keys.
     *
     * @param  array<string, string>  $translations  Existing translations
     * @param  array<string>  $keys  Keys found in source files
     */
    public static function hasMissing(array $translations, array $keys): bool
    {
        return count(static::missing($translations, $keys)) > 0;
    }

    /**
     * Add missing keys to translation array.
     *
     * Missing keys are added with the key as the value (untranslated).
     *
     * @param  array<string, string>  $translations  Existing translations
     * @param  array<string>  $keys  Keys to add if missing
     * @return array<string, string> Updated translations array
     */
    public static function addMissing(array $translations, array $keys): array
    {
        $missing = static::missing($translations, $keys);

        foreach ($missing as $key) {
            $translations[$key] = $key; // Add with key as value (untranslated)
        }

        return $translations;
    }

    /**
     * Find unused translation keys.
     *
     * Returns keys that exist in translations but are not found in source files.
     *
     * @param  array<string, string>  $translations  Existing translations
     * @param  array<string>  $usedKeys  Keys found in source files
     * @return array<string> Array of unused keys
     */
    public static function unused(array $translations, array $usedKeys): array
    {
        $translationKeys = array_keys($translations);
        $usedKeysFlipped = array_flip($usedKeys);

        return array_values(array_filter(
            $translationKeys,
            fn ($key) => ! isset($usedKeysFlipped[$key])
        ));
    }

    /**
     * Check if there are unused translation keys.
     *
     * @param  array<string, string>  $translations  Existing translations
     * @param  array<string>  $usedKeys  Keys found in source files
     */
    public static function hasUnused(array $translations, array $usedKeys): bool
    {
        return count(static::unused($translations, $usedKeys)) > 0;
    }

    /**
     * Remove unused keys from translation array.
     *
     * @param  array<string, string>  $translations  Existing translations
     * @param  array<string>  $usedKeys  Keys found in source files
     * @return array<string, string> Cleaned translations array
     */
    public static function removeUnused(array $translations, array $usedKeys): array
    {
        $usedKeysFlipped = array_flip($usedKeys);

        return array_filter(
            $translations,
            fn ($value, $key) => isset($usedKeysFlipped[$key]),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
