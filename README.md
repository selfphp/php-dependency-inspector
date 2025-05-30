# PHP Dependency Inspector

**CLI tool for analyzing, cleaning up, and monitoring Composer dependencies in PHP projects.**

---

## 🚀 Commands

### 🔍 analyse

```bash
php bin/phpdi analyse [--path=...] [--only-unused] [--output=...]
```

| Option            | Description                                                        |
|------------------|--------------------------------------------------------------------|
| `--path=...`      | Path to project root (default: current dir)                        |
| `--only-unused`   | Show only packages that are not used in the codebase               |
| `--output=...`    | Export results to a Markdown file                                  |

### 🛡 audit (for CI / Cron)

```bash
php bin/phpdi audit --output=report.md [--path=...] [--threshold=0] [--exit-on-unused] [--exit-on-outdated=minor|major] [--output-json=report.json] [--max-outdated=5] [--fail-if-total-packages-exceeds=100] [--no-ansi]
```

| Option                            | Description                                                                 |
|----------------------------------|-----------------------------------------------------------------------------|
| `--path=...`                     | Project directory to analyze                                                |
| `--output=...`                   | Write Markdown report to file                                               |
| `--output-json=...`              | Write JSON report to file                                                   |
| `--threshold=...`                | Allow up to N unused packages before failing                                |
| `--exit-on-unused`               | Return exit code `1` if unused packages exceed threshold                    |
| `--exit-on-outdated`             | Set to `none`, `minor`, or `major` to fail (exit code `2`) on outdated deps |
| `--max-outdated=...`             | Max number of outdated packages before failing with code `2`               |
| `--fail-if-total-packages-exceeds=...` | Fail with exit code `3` if total package count exceeds limit         |
| `--no-ansi`                      | Disable ANSI colors (for CI log compatibility)                              |

#### Exit Codes

- `0`: All checks passed
- `1`: Too many unused packages
- `2`: Outdated packages violate threshold
- `3`: Total package count exceeds limit

---

## ✅ Example

```bash
php bin/phpdi audit --output=report.md --threshold=3 --exit-on-unused --exit-on-outdated=minor
```

---

## 🧪 Testing

```bash
composer test
```

Runs PHPUnit tests for core functionality.

---

## 📦 Installation

```bash
composer install
```

Make sure you have a valid `composer.lock` file in your project root.

---

## 🔄 CI Integration

### GitHub Actions

`.github/workflows/dependency-audit.yml`
```yaml
name: Dependency Audit
on: [push, pull_request]

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - run: composer install
      - run: php bin/phpdi audit --exit-on-unused --exit-on-outdated=major --threshold=0
```

### GitLab CI

`.gitlab-ci.yml`
```yaml
dependency-audit:
  image: php:8.2
  script:
    - apt-get update && apt-get install -y unzip git
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
    - php bin/phpdi audit --exit-on-unused --exit-on-outdated=major --threshold=0
```
