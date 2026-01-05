<?php

namespace Kanekescom\Lingo;

/**
 * Chainable builder for translation operations.
 *
 * Provides a chainable API for working with translation arrays.
 *
 * @example
 * Lingo::make($translations)
 *     ->addMissing($keys)
 *     ->sortKeys()
 *     ->clean()
 *     ->save('lang/id.json');
 */
class LingoBuilder
{
    /**
     * The translation array being manipulated.
     *
     * @var array<string, string>
     */
    protected array $translations;

    /**
     * The locale code for auto-save functionality.
     *
     * @var string|null
     */
    protected ?string $locale = null;

    /**
     * Create a new LingoBuilder instance.
     *
     * @param  array<string, string>  $translations
     * @param  string|null  $locale
     */
    public function __construct(array $translations = [], ?string $locale = null)
    {
        $this->translations = $translations;
        $this->locale = $locale;
    }

    /**
     * Create a new LingoBuilder instance (static factory).
     *
     * @param  array<string, string>  $translations
     * @return static
     */
    public static function make(array $translations = []): static
    {
        return new static($translations);
    }

    /**
     * Set locale and load translations (instance method).
     *
     * Allows chaining from lingo() helper: lingo()->setLocale('id')
     *
     * @param  string  $locale  Locale code (e.g., 'id', 'en')
     * @return $this
     */
    public function setLocale(string $locale): static
    {
        $filePath = lang_path("{$locale}.json");
        $this->locale = $locale;

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $this->translations = json_decode($content, true) ?? [];
        }

        return $this;
    }

    /**
     * Re-read file and remove duplicate keys.
     *
     * Only works when locale is set via setLocale().
     * Keeps the last occurrence of duplicate keys.
     *
     * @return $this
     */
    public function removeDuplicates(): static
    {
        if ($this->locale !== null) {
            $filePath = lang_path("{$this->locale}.json");

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $this->translations = Lingo::removeDuplicates($content);
            }
        }

        return $this;
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
     * Add missing keys to translation array.
     *
     * Missing keys are added with the key as the value (untranslated).
     *
     * @param  array<string>  $keys  Keys to add if missing
     * @return $this
     */
    public function addMissing(array $keys): static
    {
        $this->translations = Lingo::addMissing($this->translations, $keys);

        return $this;
    }

    /**
     * Remove unused keys from translation array.
     *
     * Removes keys that are not found in the provided used keys list.
     *
     * @param  array<string>  $usedKeys  Keys found in source files
     * @return $this
     */
    public function removeUnused(array $usedKeys): static
    {
        $this->translations = Lingo::removeUnused($this->translations, $usedKeys);

        return $this;
    }

    /**
     * Sync translations with source files in a directory.
     *
     * Scans the directory, adds missing keys, and removes unused keys in one step.
     * If no path provided, defaults to resource_path('views').
     *
     * @param  string|null  $scanPath  Directory to scan (default: 'resources/views')
     * @return $this
     */
    public function syncWith(?string $scanPath = null): static
    {
        $path = $scanPath ?? resource_path('views');
        $usedKeys = Lingo::scanDirectory($path);

        return $this
            ->addMissing($usedKeys)
            ->removeUnused($usedKeys);
    }

    /**
     * Scan views and add missing translation keys.
     *
     * Shorthand for scanning and adding missing keys without removing unused.
     *
     * @param  string|null  $scanPath  Directory to scan (default: 'resources/views')
     * @return $this
     */
    public function scanAndAdd(?string $scanPath = null): static
    {
        $path = $scanPath ?? resource_path('views');
        $usedKeys = Lingo::scanDirectory($path);

        return $this->addMissing($usedKeys);
    }

    /**
     * Scan views and remove unused translation keys.
     *
     * Shorthand for scanning and removing unused keys without adding missing.
     *
     * @param  string|null  $scanPath  Directory to scan (default: 'resources/views')
     * @return $this
     */
    public function scanAndRemove(?string $scanPath = null): static
    {
        $path = $scanPath ?? resource_path('views');
        $usedKeys = Lingo::scanDirectory($path);

        return $this->removeUnused($usedKeys);
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
            fn($value) => $value !== '' && $value !== null
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
     * @param  callable  $callback
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
     * If no path is provided and locale is set, saves to lang/{locale}.json
     *
     * @param  string|null  $filePath  Path to save JSON file (optional if locale is set)
     * @param  bool  $sort  Whether to sort keys before saving
     * @return bool  True if saved successfully
     *
     * @throws \InvalidArgumentException  If no path provided and locale is not set
     */
    public function save(?string $filePath = null, bool $sort = true): bool
    {
        if ($filePath === null) {
            if ($this->locale === null) {
                throw new \InvalidArgumentException('No file path provided. Either pass a path or use setLocale().');
            }
            $filePath = lang_path("{$this->locale}.json");
        }

        return Lingo::save($filePath, $this->translations, $sort);
    }

    /**
     * Export to formatted JSON string.
     *
     * @param  bool  $sortKeys  Whether to sort keys before export
     * @return string
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
        return $this->get();
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
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->translations);
    }

    /**
     * Check if translations is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->translations);
    }

    /**
     * Check if translations is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }
}
