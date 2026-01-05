<?php

namespace Kanekescom\Lingo\Commands\Concerns;

/**
 * Trait for resolving translation file paths.
 */
trait ResolvesTranslationFile
{
    /**
     * Resolve file path from locale or path.
     */
    protected function resolveFilePath(string $locale): string
    {
        // If it's already a path (contains / or \ or ends with .json)
        if (str_contains($locale, '/') || str_contains($locale, '\\') || str_ends_with($locale, '.json')) {
            return $locale;
        }

        // Try common Laravel paths
        $possiblePaths = [
            base_path("lang/{$locale}.json"),
            lang_path("{$locale}.json"),
            resource_path("lang/{$locale}.json"),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Default to lang/{locale}.json
        return base_path("lang/{$locale}.json");
    }

    /**
     * Load and validate translation file.
     *
     * @return array{file: string, content: string, translations: array}|null
     */
    protected function loadTranslationFile(string $locale): ?array
    {
        $file = $this->resolveFilePath($locale);

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            $this->newLine();
            $this->line('<fg=gray>Tip: You can specify locale (e.g., id) or full path (e.g., lang/id.json)</>');

            return null;
        }

        $content = file_get_contents($file);
        $translations = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: '.json_last_error_msg());

            return null;
        }

        return [
            'file' => $file,
            'content' => $content,
            'translations' => $translations,
        ];
    }

    /**
     * Truncate string for display.
     */
    protected function truncate(string $text, int $length = 60): string
    {
        return strlen($text) > $length ? substr($text, 0, $length).'...' : $text;
    }
}
