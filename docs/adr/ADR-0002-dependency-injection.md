# ADR-0002: Constructor Injection as Dependency Management Pattern

## Status

Accepted — 2026-05-09

## Context

The MVP of the project uses static singleton classes (`ConfigService`, `LocalizationService`) for global access to
configuration and localisation. This worked for the initial set of commands, but the project is growing: new services
with their own dependencies are being added (Last.fm API client, SQLite repositories, MoodResolver, FFmpeg processor).

With the singleton approach, new dependencies become hidden — a class silently accesses global state without declaring
what it needs. This makes the code harder to understand and makes testing impossible.

## Decision

Use **constructor injection** for all new services. Dependencies are assembled manually in `bin/console`
(Simple DI without a container).

Existing `ConfigService` and `LocalizationService` remain as singletons — migration is not justified by the amount
of work required given the absence of tests for these classes.

## Rules

### Required for all new services

```php
// ✅ Correct — dependencies via constructor
final class LastFmClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly HttpClientInterface $http,
    ) {}
}

final class MoodResolver
{
    public function __construct(
        private readonly MoodConfig $config,
        private readonly LastFmClient $lastFm,
    ) {}
}

// ❌ Forbidden — hidden access to global state
final class LastFmClient
{
    public function getTags(): array
    {
        $key = ConfigService::get('lastfm.key'); // violation
    }
}
```

### Dependency graph assembly — only in `bin/console`

```php
// bin/console — the only place where services are instantiated
$config     = ConfigService::init();
$http       = new HttpClient();
$lastFm     = new LastFmClient($config->get('lastfm.key'), $http);
$cache      = new SqliteCache($config->get('db.path'));
$moodConfig = new MoodConfig($config->getMoods());
$resolver   = new MoodResolver($moodConfig, $lastFm, $cache);

$application->add(new EnrichCommand($lastFm, $cache));
$application->add(new OrganizeCommand($resolver));
```

### What remains singleton (exception)

- `ConfigService` — global configuration, initialised once at startup
- `LocalizationService` — global localisation, used via the `__()` helper

New code must not introduce new singleton classes.

## Rationale

**Explicit contract.** The class constructor is the documentation of its dependencies. Opening a class immediately
shows what it needs — without reading the implementation.

**Predictable initialisation.** An object cannot exist without its dependencies — the constructor guarantees this at
the language level. The "was `init()` already called?" problem disappears.

**Configuration flexibility.** Different instances of a service can be created with different parameters without
modifying global state.

**No new dependencies.** Simple DI does not require `symfony/dependency-injection` or other packages — the dependency
graph is assembled in `bin/console` manually.

## Consequences

### Positive

- Each class's dependencies are visible without reading its implementation
- A new service can easily be configured with different parameters without changing global state
- Code is ready for testing when tests are added
- No new packages required

### Negative

- `bin/console` will grow with each new service
- Requires discipline — it is easy to accidentally write `ConfigService::get()` in a new service

### Neutral

- Existing code (`ConfigService`, `LocalizationService`, MVP commands) is not changed
- If the project grows significantly, consider `symfony/dependency-injection` as a separate architectural decision
  (new ADR)
