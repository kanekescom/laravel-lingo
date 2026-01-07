<?php

namespace Kanekescom\Lingo;

/**
 * Chainable builder for translation operations.
 *
 * Provides a chainable API for working with translation arrays.
 *
 * @example
 * Lingo::load('id.json')->sortKeys()->save();
 * Lingo::make($translations)->clean()->save('output.json');
 */
class LingoBuilder
{
    /**
     * @var array<string, string>
     */
    protected array $translations = [];

    /**
     * @var string|null File path for save operations
     */
    protected ?string $filePath = null;

    /**
     * Create a new LingoBuilder instance.
     *
     * @param  array<string, string>  $translations
     */
    public function __construct(array $translations = [], ?string $filePath = null)
    {
        $this->translations = $translations;
        $this->filePath = $filePath;
    }

    /**
     * Create a new LingoBuilder instance (static factory).
     *
     * @param  array<string, string>  $translations
     * @param  string|null  $locale  Optional locale code for auto-save path
     * @return static
     */
    public static function make(array $translations = [], ?string $locale = null): self
    {
        $filePath = $locale ? lang_path("{$locale}.json") : null;

        return new self($translations, $filePath);
    }

    /**
     * Set the target locale for save operations.
     *
     * @param  string  $locale  Locale code (e.g., 'id', 'en')
     * @return $this
     *
     * @example
     * lingo(['Hello' => 'Halo'])->to('id')->save();
     */
    public function to(string $locale): static
    {
        $this->filePath = lang_path("{$locale}.json");

        return $this;
    }

    /**
     * Load translations by locale code.
     *
     * Automatically resolves to lang_path('{locale}.json').
     *
     * @param  string  $locale  Locale code (e.g., 'id', 'en', 'fr')
     * @return static
     *
     * @example
     * LingoBuilder::locale('id')->sortKeys()->save();
     */
    public static function locale(string $locale): self
    {
        $filePath = lang_path("{$locale}.json");

        $translations = [];
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $translations = json_decode($content, true) ?? [];
        }

        return new self($translations, $filePath);
    }

    /**
     * Load translations from a JSON file.
     *
     * @param  string  $filePath  Path to JSON file (relative to lang_path or absolute)
     * @return static
     */
    public static function load(string $filePath): self
    {
        // If not absolute path, assume it's relative to lang_path
        if (! str_starts_with($filePath, '/') && ! preg_match('/^[A-Za-z]:/', $filePath)) {
            $filePath = lang_path($filePath);
        }

        $translations = [];
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $translations = json_decode($content, true) ?? [];
        }

        return new self($translations, $filePath);
    }

    /**
     * Sort translation keys alphabetically.
     *
     * @param  bool  $ascending  Sort ascending (A-Z) if true, descending (Z-A) if false
     * @return $this
     */
    public function sortKeys(bool $ascending = true): static
    {
        $this->translations = Lingo::sortKeys($this->translations, $ascending);

        return $this;
    }

    /**
     * Clean translation array (remove empty values and sort keys).
     *
     * @return $this
     */
    public function clean(): static
    {
        $this->translations = Lingo::clean($this->translations);

        return $this;
    }

    /**
     * Add keys to translation array.
     *
     * Keys not already present are added with the key as the value (untranslated).
     *
     * @param  array<string>  $keys  Keys to add if not present
     * @return $this
     */
    public function add(array $keys): static
    {
        $this->translations = Lingo::addMissing($this->translations, $keys);

        return $this;
    }

    /**
     * Remove keys from translation array.
     *
     * Keeps only keys that are in the provided list.
     *
     * @param  array<string>  $keysToKeep  Keys to keep
     * @return $this
     */
    public function remove(array $keysToKeep): static
    {
        $this->translations = Lingo::removeUnused($this->translations, $keysToKeep);

        return $this;
    }

    /**
     * Sync translations with source files.
     *
     * Scans for translation keys in source files, adds missing keys,
     * and removes unused keys in one step.
     *
     * Paths are resolved relative to base_path() and support both files and directories.
     *
     * @param  string|array<string>|null  $paths  File or directory paths relative to application root (default: resource_path('views'))
     * @return $this
     *
     * @example
     * Lingo::locale('id')->sync()->save();                                     // Sync with views
     * Lingo::locale('id')->sync('app/Filament')->save();                       // Sync with one folder
     * Lingo::locale('id')->sync(['resources/views', 'app/Filament'])->save();  // Sync with multiple
     * Lingo::locale('id')->sync('app/Http/Controllers/Home.php')->save();      // Sync with single file
     */
    public function sync(string|array|null $paths = null): static
    {
        // Handle default
        if ($paths === null) {
            $paths = [resource_path('views')];
        }

        // Normalize to array
        if (is_string($paths)) {
            $paths = [$paths];
        }

        // Collect all keys from all paths
        $allKeys = [];
        foreach ($paths as $path) {
            // Use scan() which handles path resolution and supports files/directories
            $keys = Lingo::scan($path);
            $allKeys = array_merge($allKeys, $keys);
        }
        $allKeys = array_unique($allKeys);

        $this->translations = Lingo::addMissing($this->translations, $allKeys);
        $this->translations = Lingo::removeUnused($this->translations, $allKeys);

        return $this;
    }

    /**
     * Remove empty values from translation array.
     *
     * @return $this
     */
    public function removeEmpty(): static
    {
        $this->translations = array_filter(
            $this->translations,
            fn($value) => $value !== ''
        );

        return $this;
    }

    /**
     * Filter to only untranslated items.
     *
     * @return $this
     */
    public function onlyUntranslated(): static
    {
        $this->translations = Lingo::untranslated($this->translations);

        return $this;
    }

    /**
     * Filter to only translated items.
     *
     * @return $this
     */
    public function onlyTranslated(): static
    {
        $this->translations = Lingo::translated($this->translations);

        return $this;
    }

    /**
     * Apply a custom callback to the translations.
     *
     * @return $this
     */
    public function tap(callable $callback): static
    {
        $callback($this->translations);

        return $this;
    }

    /**
     * Transform translations using a callback.
     *
     * @param  callable  $callback  Receives translations array, should return array
     * @return $this
     */
    public function transform(callable $callback): static
    {
        $this->translations = $callback($this->translations);

        return $this;
    }

    /**
     * Merge another translation array.
     *
     * @param  array<string, string>  $translations
     * @return $this
     */
    public function merge(array $translations): static
    {
        $this->translations = array_merge($this->translations, $translations);

        return $this;
    }

    /**
     * Save translation array to JSON file.
     *
     * If no path is provided, uses the path from load() or locale().
     * Falls back to app's current locale if no path is set.
     *
     * @param  string|null  $filePath  Path to save JSON file (optional)
     * @param  bool  $sort  Whether to sort keys before saving
     * @return bool True if saved successfully
     */
    public function save(?string $filePath = null, bool $sort = true): bool
    {
        $path = $filePath ?? $this->filePath;

        // Fallback to app's current locale
        if ($path === null) {
            $path = lang_path(app()->getLocale() . '.json');
        }

        return Lingo::save($path, $this->translations, $sort);
    }

    /**
     * Export to formatted JSON string.
     *
     * @param  bool  $sortKeys  Whether to sort keys before export
     */
    public function toJson(bool $sortKeys = true): string
    {
        return Lingo::toJson($this->translations, $sortKeys);
    }

    /**
     * Get the translation array.
     *
     * @return array<string, string>
     */
    public function get(): array
    {
        return $this->translations;
    }

    /**
     * Get the translation array (alias for get).
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->translations;
    }

    /**
     * Get translation statistics.
     *
     * @return array{total: int, translated: int, untranslated: int, percentage: float}
     */
    public function stats(): array
    {
        return Lingo::stats($this->translations);
    }

    /**
     * Get count of translations.
     */
    public function count(): int
    {
        return count($this->translations);
    }

    /**
     * Check if translations is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->translations);
    }

    /**
     * Check if translations is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! empty($this->translations);
    }
}
