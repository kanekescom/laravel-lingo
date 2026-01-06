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
- 🔧 **Artisan commands** for CLI management

## Installation

```bash
composer require kanekescom/laravel-lingo
```

## Usage

### Basic Usage

```php
use Kanekescom\Lingo\Facades\Lingo;

// Load by locale and manipulate
Lingo::locale('id')->sortKeys()->save();
Lingo::locale('id')->clean()->save();
Lingo::locale('id')->sync()->save();                              // Sync with views
Lingo::locale('id')->sync(['resources/views', 'app'])->save();    // Sync multiple folders

// Get data
$stats = Lingo::locale('id')->stats();
$untranslated = Lingo::locale('id')->onlyUntranslated()->get();

// Create from array (overwrites file)
lingo(['Hello' => 'Halo'], 'id')->save();

// Merge with existing translations
Lingo::locale('id')->merge(['Hello' => 'Halo'])->save();

// Without locale - fallback to app()->getLocale()
lingo(['Hello' => 'Halo'])->save();
```

### Artisan Commands

All commands accept an optional `locale` argument. If omitted, defaults to `config('app.locale')`.

```bash
# Check for issues (duplicates, untranslated)
php artisan lingo:check                   # Uses app locale
php artisan lingo:check id                # Specify locale

# Clean translation file (remove duplicates, empty, sort)
php artisan lingo:clean                   # Uses app locale
php artisan lingo:clean id                # Specify locale
php artisan lingo:clean id --keep-empty   # Keep empty values

# Show translation statistics
php artisan lingo:stats                   # Uses app locale
php artisan lingo:stats id --detailed     # With samples

# Sort keys alphabetically
php artisan lingo:sort                    # Uses app locale
php artisan lingo:sort id --desc          # Z-A order

# Sync with source files (find __(), trans(), @lang() calls)
php artisan lingo:sync                              # Scan resources/views
php artisan lingo:sync id --path=resources/views    # Scan views directory
php artisan lingo:sync id --path=app/Filament       # Scan Filament resources
php artisan lingo:sync id --add                     # Only add missing keys
php artisan lingo:sync id --remove                  # Only remove unused keys
php artisan lingo:sync id --dry-run                 # Preview changes
```

### Available Methods

#### Entry Points

| Method | Description |
|--------|-------------|
| `Lingo::locale('id')` | Load by locale → `lang/id.json` |
| `Lingo::fromFile($path)` | Load from custom file path |
| `Lingo::make($arr, $locale)` | Create from array with optional locale |
| `lingo($arr, 'id')` | Helper: create from array with locale |

#### Chainable Methods

| Method | Description |
|--------|-------------|
| `to($locale)` | Set target locale for save() |
| `sync($paths)` | Sync with source files (default: views) |
| `sortKeys($asc)` | Sort keys alphabetically (default: A-Z) |
| `clean()` | Remove empty values and sort keys |
| `merge($arr)` | Merge with another array |
| `add($keys)` | Add keys if not present |
| `remove($keys)` | Keep only keys in list |
| `removeEmpty()` | Remove empty values |
| `onlyUntranslated()` | Filter to untranslated items |
| `onlyTranslated()` | Filter to translated items |
| `transform($fn)` | Transform translations with callback |
| `tap($fn)` | Inspect translations without modifying |

#### Output Methods

| Method | Description |
|--------|-------------|
| `save($path)` | Save to file (path optional) |
| `toJson()` | Export as JSON string |
| `get()` | Get translations array |
| `toArray()` | Alias for get() |
| `stats()` | Get translation statistics |
| `count()` | Get number of translations |
| `isEmpty()` | Check if translations empty |
| `isNotEmpty()` | Check if translations not empty |

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
