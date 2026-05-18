# Testing Guide — music-resort

## Testing Strategy

The project uses **Pest** for both unit and integration tests.

- **Unit tests** — test individual services and pure logic in isolation,
  without touching the filesystem or spawning processes.
- **Integration tests** — run real console commands against the `samples/` directory
  in `--dry-run` mode. No files are moved or deleted.

No E2E or contract tests — the tool has no HTTP API, no database, and no external services.

### `samples/` directory

`samples/` contains a small set of real audio files for development and manual testing.
Integration tests use it as a read-only input (always with `--dry-run`).
Do not use production music files for tests.

---

## Testing Stack

| Tool | Version | Purpose |
|------|---------|---------|
| Pest | ^3.8 | Test runner and assertion library |
| PHPUnit | (via Pest) | Base test infrastructure |
| symfony/process | ^7.3 | Spawning console commands in integration tests |

---

## Test File Conventions

### Directory structure

```
tests/
    TestCase.php            ← base test case (extend for shared setup)
    Unit/                   ← unit tests (services, pure functions)
        Mp3ResortServiceTest.php
        FileResortServiceTest.php
        ...
    Integration/            ← integration tests (real commands, --dry-run)
        ConsoleCommandsTest.php
        ...
```

### File naming

- One test file per class or concern: `{ClassName}Test.php`
- Placed in `Unit/` or `Integration/` mirroring the concern, not the `src/` structure

### Test structure (Pest functional style)

```php
describe('ServiceName', function (): void {
    describe('methodName', function (): void {
        it('does something specific', function (): void {
            expect($result)->toBe($expected);
        });
    });
});
```

### Naming rules

- `describe()` outer block — class or feature name
- `describe()` inner block — method or behaviour group
- `it()` — what the test asserts, written as a plain sentence

```php
// ✅ Good
it('returns the first artist from a semicolon-separated string')

// ❌ Too vague
it('works correctly')
it('test artist')
```

---

## Running Tests

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# Single file
vendor/bin/pest tests/Unit/Mp3ResortServiceTest.php

# Watch mode (re-run on file change)
vendor/bin/pest --watch
```

No prerequisites — integration tests use `samples/` (committed) and always run with `--dry-run`.

---

## Writing Tests

### Unit test example — pure function

```php
describe('Artist extraction', function (): void {
    it('returns the first artist from a multi-artist string', function (string $input, string $expected): void {
        $pattern = '/\s*(?:;|,|\/|&|\s+feat\.?|\s+ft\.?|\s+featuring)\s*/i';
        $parts = preg_split($pattern, $input, -1, PREG_SPLIT_NO_EMPTY);
        $first = trim($parts[0] ?? $input);

        expect($first)->toBe($expected);
    })->with([
        'semicolon separator' => ['Artist A; Artist B', 'Artist A'],
        'feat separator'      => ['Artist A feat. Artist B', 'Artist A'],
        'single artist'       => ['Artist A', 'Artist A'],
    ]);
});
```

### Integration test example — console command

```php
use Symfony\Component\Process\Process;

describe('music:resort --dry-run', function (): void {
    it('runs on the samples directory without errors', function (): void {
        $process = new Process([
            'php', 'bin/console', 'music:resort',
            'samples/', 'samples/dest-test/',
            '--dry-run',
        ]);
        $process->run();

        expect($process->isSuccessful())->toBeTrue();
    });
});
```

### Rules for tests

- **Unit tests must not touch the filesystem** — mock or use in-memory data
- **Integration tests always use `--dry-run`** — never modify real files
- **No shared mutable state between tests** — each test is self-contained
- **Private methods** — if a private method needs direct testing, it is a sign it should be
  extracted to a separate class or static helper
- **No `sleep()` or arbitrary timeouts** in tests

---

## Coverage Requirements

No enforced coverage threshold yet. Goal for first milestone:

| Area | Target |
|------|--------|
| Pure functions and helpers | 100% |
| Service public methods | ≥ 80% |
| Integration (smoke) | all commands boot and run on samples |

To generate a coverage report (requires Xdebug or PCOV):

```bash
vendor/bin/pest --coverage
vendor/bin/pest --coverage --min=80
```

---

## CI Integration

Not yet configured. Planned GitHub Actions step:

```yaml
- name: Run tests
  run: composer test
```

Integration tests will run in CI as-is — `samples/` is committed and `--dry-run` is always used.

---

## Known Gaps & Tech Debt

| Gap | Reason | Tracked |
|-----|--------|---------|
| `extractFirstArtist` is private in `Mp3ResortService` | Cannot unit-test directly | Extract to helper |
| `sanitizeFolderName` is private in `FileResortService` | Cannot unit-test directly | Extract to helper |
| `Mp3ResortService` instantiates `MusicMetadataService` and `FileResortService` internally | DI violation blocks proper unit testing | Fix DI — see coding-standards.md |
| No coverage threshold enforced | Early stage | Add when first unit tests are written |
