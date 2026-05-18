# Deployment Guide — music-resort

## Environments

| Environment | Purpose | Dry-run | Dev tooling |
|-------------|---------|---------|-------------|
| `local` (dev) | Development, testing, debugging | `DEBUG=true` — all commands dry-run | Yes (`pnpm install`) |
| `production` | Real music library operations | `DEBUG=false` | No |

The only difference between environments is the `.env` file and whether dev dependencies are installed.
There is no server, no Docker, no remote deployment — the tool runs entirely on the local machine.

---

## Local Development Setup

From a fresh clone:

1. **Clone the repository**
   ```bash
   git clone https://github.com/indigo-soft/music-resort
   cd music-resort
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dev tooling** (commitlint, lefthook, release-it)
   ```bash
   pnpm install
   ```

4. **Install git hooks and make scripts executable**
   ```bash
   pnpm setup
   ```

   > ⚠️ Use `pnpm setup`, not `pnpm prepare`. Running lefthook inside pnpm's `prepare`
   > lifecycle hook conflicts with pnpm's module resolution and breaks `node_modules`.

5. **Configure environment**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` — set `DEBUG=true` to enable global dry-run:
   ```dotenv
   DEBUG=true
   DEFAULT_LANG=en
   ```

6. **Verify setup**
   ```bash
   composer test
   php bin/console list
   ```

---

## Production Setup

For using the tool on a real music library (files will be modified):

1. **Clone the repository**
   ```bash
   git clone https://github.com/indigo-soft/music-resort
   cd music-resort
   ```

2. **Install PHP dependencies** (no dev packages)
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   ```
   Edit `.env`:
   ```dotenv
   DEBUG=false
   DEFAULT_LANG=en
   ```

4. **Verify setup**
   ```bash
   php bin/console list
   ./music list
   ```

---

## Updating to a New Version

### Development environment

```bash
git pull origin main
composer install
pnpm install
```

### Production environment

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

---

## Configuration & Secrets

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `DEBUG` | bool | `false` | `true` forces dry-run globally on all commands |
| `DEFAULT_LANG` | string | `en` | UI locale — `en` or `uk` |

- `.env` is in `.gitignore` — never committed
- `.env.example` is the reference template — always keep it up to date
- No secrets required — the tool has no API keys or external service credentials

---

## CI/CD Pipeline

No CI/CD pipeline is configured yet.

Planned: standard GitHub Actions workflow from docs.template covering:
- Install → lint → test on every push and pull request
- Release automation on tag push (`v*.*.*`)

---

## Rollback Procedure

```bash
git fetch --tags
git checkout v1.2.3
composer install --no-dev --optimize-autoloader   # production
composer install                                   # development
```

---

## Post-Setup Verification

```bash
php bin/console list
php bin/console music:resort samples/ /tmp/music-test --dry-run
composer test
```
