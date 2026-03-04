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
****
## How It Works

### Key Concepts

#### Translation File Format

Lingo works with Laravel's **JSON translation files** (not PHP array files). These files are located at `lang/{locale}.json`:

```json
{
    "Hello": "Halo",
    "Welcome": "Selamat Datang",
    "Save": "Save"
}
```

#### Translation Status Detection

Lingo determines whether an item is translated or not by **comparing the key and value**:

- ✅ **Translated** → key ≠ value (e.g., `"Hello": "Halo"`)
- ⚠️ **Untranslated** → key = value (e.g., `"Save": "Save"`)
- 🗑️ **Empty** → value is empty string (e.g., `"Hello": ""`)

#### Duplicate Detection

Since PHP automatically overwrites duplicate keys during `json_decode()`, Lingo uses **regex on raw JSON content** to detect duplicates before parsing:

```php
// Find all "key": patterns in raw JSON
preg_match_all('/"([^"]+)"\s*:/', $jsonContent, $matches);
```

### How Source Scanning Works

The `lingo:sync` command and `sync()` method work by scanning your source code to find translation keys. The scanning process uses **4 regex patterns** to detect Laravel translation function calls:

| Pattern | Detected Examples |
|---------|-------------------|
| `__('...')` | `__('Hello')`, `__("Welcome")` |
| `trans('...')` | `trans('Save')`, `trans("Cancel")` |
| `@lang('...')` | `@lang('Submit')` (Blade directive) |
| `Lang::get('...')` | `Lang::get('Delete')` |

Scanning process:

1. **Resolve path** → Relative paths are resolved via `base_path()`, absolute paths are used directly if they exist
2. **Recursive scan** → For directories, all `.php` files are scanned recursively using `RecursiveIteratorIterator`
3. **Extract keys** → Each file's content is matched against the 4 regex patterns above
4. **Deduplicate** → All found keys are deduplicated using `array_unique()`

### Sync Workflow

`lingo:sync` is the most powerful command. Here's how it works:

```
Source Files (*.php)              Translation File (lang/id.json)
┌─────────────────┐              ┌─────────────────────────┐
│ __('Hello')      │              │ "Hello": "Halo"         │
│ __('Welcome')    │  ──scan──▶   │ "Goodbye": "Selamat..."│
│ __('New Key')    │              │                         │
└─────────────────┘              └─────────────────────────┘
         │                                    │
         ▼                                    ▼
   Found keys:                         Existing keys:
   [Hello, Welcome, New Key]           [Hello, Goodbye]
         │                                    │
         └──────────────┬─────────────────────┘
                        ▼
              ┌─── Compare ───┐
              │               │
         Missing keys    Unused keys
         [New Key]       [Goodbye]
              │               │
              ▼               ▼
   Add "New Key":"New Key"   Remove "Goodbye"
              │               │
              └───────┬───────┘
                      ▼
            Final: lang/id.json
     ┌─────────────────────────┐
     │ "Hello": "Halo"         │
     │ "New Key": "New Key"    │
     │ "Welcome": "Welcome"    │
     └─────────────────────────┘
```

**Steps:**
1. **Scan** all given paths (default: `resources/views`)
2. **Collect** all translation keys found in source code
3. **Compare** with existing keys in the JSON file
4. **Add** missing keys (value = key, so they are easily identified as untranslated)
5. **Remove** keys that are no longer used in source code
6. **Save** the updated JSON file (sorted alphabetically)

### File Path Resolution

All commands accept either a **locale code** or a **direct file path**:

```bash
php artisan lingo:check id           # → Searches: lang/id.json, resources/lang/id.json
php artisan lingo:check lang/id.json # → Used directly as file path
```

File search order:
1. `base_path("lang/{locale}.json")`
2. `lang_path("{locale}.json")`
3. `resource_path("lang/{locale}.json")`

## Installation

```bash
composer require kanekescom/laravel-lingo
```

## Usage

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
php artisan lingo:sync                              # Scan resources/views (default)
php artisan lingo:sync id --path=resources/views    # Single path
php artisan lingo:sync id --path=resources/views --path=app/Filament  # Multiple paths
php artisan lingo:sync id --path=app/Http/Controllers/HomeController.php  # Single file
php artisan lingo:sync id --add                     # Only add missing keys
php artisan lingo:sync id --remove                  # Only remove unused keys
php artisan lingo:sync id --dry-run                 # Preview changes
```

> **Note:** All paths are relative to your application root. Both files and directories are supported.

### Programmatic Usage

```php
use Kanekescom\Lingo\Facades\Lingo;

// Load by locale and manipulate
Lingo::locale('id')->sortKeys()->save();
Lingo::locale('id')->clean()->save();

// Sync with source files
Lingo::locale('id')->sync()->save();                                    // Default: resources/views
Lingo::locale('id')->sync('app/Filament')->save();                      // Single folder
Lingo::locale('id')->sync(['resources/views', 'app/Filament'])->save(); // Multiple paths
Lingo::locale('id')->sync('app/Http/Controllers/HomeController.php')->save(); // Single file

// Get data
$stats = Lingo::locale('id')->stats();
$untranslated = Lingo::locale('id')->onlyUntranslated()->get();

// Create from array (overwrites file)
lingo(['Hello' => 'Halo'], 'id')->save();

// Using helper with sync (cleaner syntax)
lingo()->sync(['resources/views', 'app/Filament'])->to('id')->save();

// Merge with existing translations
Lingo::locale('id')->merge(['Hello' => 'Halo'])->save();

// Without locale - fallback to app()->getLocale()
lingo(['Hello' => 'Halo'])->save();
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
| `sync($paths)` | Sync with source files (accepts string, array, or null for default views). Supports files and directories. |
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
