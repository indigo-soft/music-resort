# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| latest (`main`) | ✅ |
| older releases | ❌ |

Only the latest release receives security fixes.

## Scope

music-resort is a local CLI tool. It has:
- No network connections
- No web interface or API
- No database
- No external service dependencies
- No user authentication

Security concerns are limited to:
- **Local file system** — the tool reads and moves files on your machine
- **Dependencies** — third-party PHP packages (`getid3`, Symfony components)

## Reporting a Vulnerability

If you discover a security vulnerability, please **do not open a public GitHub issue**.

Report privately via GitHub:
**[Security Advisories → Report a vulnerability](https://github.com/indigo-soft/music-resort/security/advisories/new)**

Include in your report:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

You will receive a response within **7 days**.

## Dependency Security

Dependencies are kept up to date automatically via Renovate (npm) and Dependabot (GitHub Actions).
PHP dependencies are checked with `composer audit`:

```bash
composer audit
```

Run this before any production deployment.
