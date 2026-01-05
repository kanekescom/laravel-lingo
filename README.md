# Laravel Lingo

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kanekescom/laravel-lingo.svg?style=flat-square)](https://packagist.org/packages/kanekescom/laravel-lingo)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/kanekescom/laravel-lingo/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kanekescom/laravel-lingo/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/kanekescom/laravel-lingo/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/kanekescom/laravel-lingo/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kanekescom/laravel-lingo.svg?style=flat-square)](https://packagist.org/packages/kanekescom/laravel-lingo)

Laravel package to manage, sync, and analyze JSON translation files.

## Features

- 🔍 **Scan** for translation keys in your codebase
- 📊 **Statistics** on translation progress
- 🔄 **Sync** translations with source files
- 🧹 **Clean** and sort translation files
- 🔧 **Artisan command** for CLI management
- ⛓️ **Chainable API** with method chaining

## Installation

```bash
composer require kanekescom/laravel-lingo
```

## Usage

### Using the `lingo()` Helper

The `lingo()` helper provides a fluent interface similar to Laravel's `collect()`:

```php
// Load locale and get stats
lingo()->setLocale('id')->stats();

// Sync translations with views
lingo()->setLocale('id')->syncWith()->save();

// Scan and add missing keys
lingo()->setLocale('id')->scanAndAdd('app')->save();

// Scan and remove unused keys
lingo()->setLocale('id')->scanAndRemove()->save();

// Sort keys alphabetically
lingo()->setLocale('id')->sortKeys()->save();

// Clean (remove empty values and sort)
lingo()->setLocale('id')->clean()->save();

// Get only untranslated items
$untranslated = lingo()->setLocale('id')->onlyUntranslated()->get();

// Get only translated items
$translated = lingo()->setLocale('id')->onlyTranslated()->get();

// Export to JSON
$json = lingo()->setLocale('id')->toJson();
```

### Using the `Lingo` Facade

```php
use Kanekescom\Lingo\Lingo;

// Get translation statistics
$stats = Lingo::stats($translations);

// Find duplicate keys in JSON content
$duplicates = Lingo::duplicates($jsonContent);

// Sort keys alphabetically
$sorted = Lingo::sortKeys($translations);

// Find untranslated items
$untranslated = Lingo::untranslated($translations);

// Scan directory for translation keys
$keys = Lingo::scanDirectory(resource_path('views'));

// Find missing keys
$missing = Lingo::missing($translations, $keys);

// Add missing keys
$updated = Lingo::addMissing($translations, $keys);

// Remove unused keys
$cleaned = Lingo::removeUnused($translations, $usedKeys);

// Save to file
Lingo::save(lang_path('id.json'), $translations);
```

### Artisan Commands

Lingo provides focused commands for each task:

```bash
# Check for issues (duplicates, untranslated)
php artisan lingo:check id
php artisan lingo:check id --fix          # Auto-fix duplicates

# Show translation statistics
php artisan lingo:stats id
php artisan lingo:stats id --detailed     # With samples

# Sort keys alphabetically
php artisan lingo:sort id
php artisan lingo:sort id --desc          # Z-A order

# Sync with source files
php artisan lingo:sync id --path=resources/views
php artisan lingo:sync id --add           # Add missing keys
php artisan lingo:sync id --remove        # Remove unused keys
php artisan lingo:sync id --add --remove  # Both
php artisan lingo:sync id --dry-run       # Preview changes
```

### Available Methods

#### LingoBuilder (Fluent API)

| Method | Description |
|--------|-------------|
| `setLocale(string $locale)` | Set locale and load translations |
| `sortKeys(bool $ascending = true)` | Sort keys alphabetically |
| `clean()` | Remove empty values and sort keys |
| `addMissing(array $keys)` | Add missing translation keys |
| `removeUnused(array $usedKeys)` | Remove unused keys |
| `syncWith(?string $path = null)` | Sync with source files (add missing + remove unused) |
| `scanAndAdd(?string $path = null)` | Scan and add missing keys |
| `scanAndRemove(?string $path = null)` | Scan and remove unused keys |
| `removeEmpty()` | Remove empty values |
| `removeDuplicates()` | Remove duplicate keys from file |
| `onlyUntranslated()` | Filter to untranslated items |
| `onlyTranslated()` | Filter to translated items |
| `merge(array $translations)` | Merge with another array |
| `transform(callable $callback)` | Transform using callback |
| `save(?string $path = null, bool $sort = true)` | Save to file |
| `toJson(bool $sortKeys = true)` | Export as JSON string |
| `get()` / `toArray()` | Get translations array |
| `stats()` | Get translation statistics |
| `count()` | Get count of translations |
| `isEmpty()` / `isNotEmpty()` | Check if empty |

#### Lingo (Static Methods)

| Method | Description |
|--------|-------------|
| `make(array $translations = [])` | Create LingoBuilder instance |
| `duplicates(string $jsonContent)` | Find duplicate keys |
| `hasDuplicates(string $jsonContent)` | Check for duplicates |
| `removeDuplicates(string $jsonContent)` | Remove duplicates |
| `load(string $filePath)` | Load translation file |
| `save(string $filePath, array $translations, bool $sort = true)` | Save to file |
| `sortKeys(array $translations, bool $ascending = true)` | Sort keys |
| `untranslated(array $translations)` | Get untranslated items |
| `translated(array $translations)` | Get translated items |
| `stats(array $translations)` | Get statistics |
| `clean(array $translations)` | Clean translations |
| `toJson(array $translations, bool $sortKeys = true)` | Export as JSON |
| `scanDirectory(string $directory, array $extensions = ['php'])` | Scan for keys |
| `extractKeys(string $content)` | Extract keys from content |
| `missing(array $translations, array $keys)` | Find missing keys |
| `addMissing(array $translations, array $keys)` | Add missing keys |
| `unused(array $translations, array $usedKeys)` | Find unused keys |
| `removeUnused(array $translations, array $usedKeys)` | Remove unused keys |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Achmad Hadi Kurnia](https://github.com/achmadhadikurnia)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
