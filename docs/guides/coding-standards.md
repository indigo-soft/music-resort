# Coding Standards — music-resort

## Language & Runtime

| Item | Value |
|------|-------|
| Primary language | PHP 8.5+ |
| Secondary languages | Shell scripts (`music` wrapper), YAML (GitHub Actions), JSON (config files) |
| PHP standard | PSR-12 (enforced by PHP CS Fixer) |
| Strict types | **Required in every PHP file** — `declare(strict_types=1);` on line 1 |

## Formatting

Formatter: **PHP CS Fixer 3.x**
Config: `.php-cs-fixer.dist.php`

```bash
composer fix      # auto-fix all files
composer cs       # dry-run check (no changes)
```

Enforced via Lefthook `pre-commit` hook on staged PHP files.

### Key formatting rules (from `.php-cs-fixer.dist.php`)

| Rule | Setting |
|------|---------|
| Base standard | `@PSR12` |
| Array syntax | short (`[]`, not `array()`) |
| String quotes | single quotes preferred |
| `null` in union types | always last (`string\|null`, not `null\|string`) |
| Union type spacing | no spaces around `\|` (`string\|int`) |
| Import style | one import per line, alphabetical, classes before functions before constants |
| PHPDoc | required for all methods; `@param`, `@return`, `@throws` in that order |
| Class element order | use_trait → constants → properties → construct → magic → public → protected → private |
| Operators on multiline | operator goes at the **beginning** of the next line |
| Increment style | post-increment (`$i++`, not `++$i`) |
| Constants | lowercase (`true`, `false`, `null`) |
| `new` expression | without parentheses when no args (`new Finder`, not `new Finder()`) |

> See `.php-cs-fixer.dist.php` for the complete rule set.

## Linting (Static Analysis)

Tool: **PHPStan 2.x**
Config: `phpstan.neon.dist`
Level: **1**

```bash
composer stan     # run static analysis
```

Enforced via Lefthook `pre-push` hook.

PHPDoc types are treated as certain (`treatPhpDocTypesAsCertain: true`).
Analysed paths: `src/` only. `vendor/` and `samples/` are excluded.

## Naming Conventions

### Classes

| Type | Convention | Example |
|------|-----------|---------|
| Service | `PascalCase` + `Service` suffix | `Mp3ResortService` |
| Command | `PascalCase` + `Command` suffix | `ResortMp3Command` |
| Exception | `PascalCase` + `Exception` suffix | `MusicMetadataException` |
| Helper class | `PascalCase` + `Helper` suffix | `ResortMp3Helper` |
| DTO / value object | `PascalCase` | `ResortResult` |

All service classes must be declared `final`.

### Methods & Properties

| Type | Convention | Example |
|------|-----------|---------|
| Public method | `camelCase` | `getArtist()`, `isDryRun()` |
| Private method | `camelCase` | `prepareFinder()`, `processFiles()` |
| Boolean method | prefix `is` / `has` / `can` | `isDryRun()`, `hasErrors()` |
| Property | `camelCase` | `$sourceDir`, `$dryRun` |
| Constant | `UPPER_SNAKE_CASE` | `MAX_FOLDER_LENGTH` |

### Files & Folders

| Item | Convention | Example |
|------|-----------|---------|
| PHP class file | `PascalCase.php` — matches class name | `Mp3ResortService.php` |
| PHP helper file | `camelCase.php` | `helpers.php` |
| Config file | `camelCase.php` | `app.php` |
| Shell script | `kebab-case` | `music` (no extension on Linux) |
| Docs & guides | `kebab-case.md` | `coding-standards.md` |

### Translation keys

Keys use dot-notation nested arrays. Prefix defines the message type:

| Prefix | Meaning | Example key |
|--------|---------|-------------|
| `info.*` | Actual action performed | `info.resort.moved` |
| `note.*` | Dry-run equivalent ("would do X") | `note.resort.moved` |
| `error.*` | Error messages | `error.source_not_exists` |
| `warning.*` | Warnings / skipped items | `warning.file_skipped` |
| `console.*` | UI labels, args, options | `console.arg.source` |

## Import Rules

Managed by PHP CS Fixer (`ordered_imports`, `no_unused_imports`, `single_import_per_statement`):

1. **Group order:** classes → functions → constants
2. **Within each group:** alphabetical
3. **One import per line** — no grouped imports
4. **No unused imports**
5. **Fully qualified types** — always import; never use leading `\` in global namespace

```php
// ✅ Correct
use MusicResort\Exception\MusicMetadataException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;

// ❌ Wrong — grouped, unordered, unused
use Symfony\Component\Finder\Finder, Symfony\Component\Console\Command\Command;
```

## Documentation & Comments

### PHPDoc — required for all methods

```php
// ✅ Required
/**
 * Performs sorting of MP3 files into directories.
 *
 * @return array{status:int, processed:int, errors:int}
 */
public function resort(): array {}

// ✅ Single-line for simple properties
/** @var string[] */
private array $paths = [];
```

### Inline comments

- Use `//` for single-line, `/* */` for multi-line
- Comments explain **why**, not **what**
- No commented-out code in committed files (use git history)

## Forbidden Patterns

### Architecture violations

```php
// ❌ Business logic in Command — delegate to Service
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // sorting logic here — must not be here
}

// ❌ new Service() inside another Service — inject via constructor
private function processSingleFile(string $path): void
{
    $service = new FileResortService(...); // ← must not be here
}
```

> **⚠️ Tech debt:** `Mp3ResortService::processSingleFile()` currently violates this rule —
> it instantiates `MusicMetadataService` and `FileResortService` directly inside a service method.
> This must be fixed. Do not replicate this pattern anywhere.

```php
// ❌ Reading --dry-run directly in a Service
$isDryRun = $input->getOption('dry-run');

// ✅ Use ConsoleCommandService
$isDryRun = $commandService->isDryRun(); // combines --dry-run + app.debug
```

```php
// ❌ Calling ConfigService or LocalizationService in new Services
$debug = ConfigService::get('app.debug');

// ✅ Inject the value via constructor
public function __construct(private readonly bool $debug) {}
```

### Code quality

```php
// ❌ Non-strict comparison
if ($result == true) {}

// ✅ Strict comparison
if ($result === true) {}

// ❌ Swallowed exception
try {
    $this->processFile($path);
} catch (Exception $e) {
    // silently ignored
}

// ✅ Always log or rethrow
} catch (Exception $e) {
    $this->io->warning(__('console.warning.file_skipped', ['file' => $path, 'message' => $e->getMessage()]));
}

// ❌ Magic number
if ($name > 100) {}

// ✅ Named constant or documented variable
$maxLength = 100; // FileResortService::sanitizeFolderName max
if ($name > $maxLength) {}
```

### Metadata access

```php
// ❌ Using getid3 directly anywhere outside MusicMetadataService
$id3 = new getID3();
$info = $id3->analyze($path);

// ✅ Use the service
$metadata = new MusicMetadataService($path);
$artist = $metadata->getArtist();
```

## Error Handling

- **Always use custom exception classes** from `src/Exception/` for domain errors
- **Never swallow exceptions silently** — log via `$io->warning()` or rethrow
- **Commands return `Command::SUCCESS` or `Command::FAILURE`** — never throw from `execute()`
- **Services throw** domain exceptions; commands catch and convert to exit codes
- **File operation errors** are caught per-file — one bad file must not abort the entire run

```php
// ✅ Per-file error handling pattern
foreach ($finder as $file) {
    try {
        $this->processSingleFile($file->getRealPath());
        $processedCount++;
    } catch (Exception $e) {
        $errorCount++;
        $this->io->warning(...);
    }
}
```

## Enforcement

| Check | Tool | When |
|-------|------|------|
| Code style | PHP CS Fixer | Lefthook `pre-commit` (staged files) |
| Static analysis | PHPStan level 1 | Lefthook `pre-push` |
| Tests | Pest | Lefthook `pre-push` |
| Commit message | commitlint | Lefthook `commit-msg` |
| Branch name | commitlint | Lefthook `pre-push` |

> ⚠️ Lefthook requires `pnpm install` + `pnpm prepare` to be run after cloning.
> Until `package.json` is added to the project, install Lefthook manually.

Run all checks locally before pushing:

```bash
composer lint    # PHP CS Fixer + PHPStan
composer test    # Pest
```
