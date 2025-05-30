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

---

### 🛡 audit (for CI / Cron)

```bash
php bin/phpdi audit [options]
```

| Option                                | Description                                                                 |
|--------------------------------------|-----------------------------------------------------------------------------|
| `--path=...`                         | Project directory to analyze                                                |
| `--output=report.md`                | Write Markdown report to file                                               |
| `--output-json=report.json`        | Write JSON report to file                                                   |
| `--threshold=3`                     | Allow up to N unused packages before failing                                |
| `--exit-on-unused`                  | Return exit code `1` if unused packages exceed threshold                    |
| `--exit-on-outdated=minor|major|none`| Return exit code `2` if outdated dependencies violate policy                |
| `--max-outdated=5`                  | Fail if more than N outdated packages are found (exit code `2`)             |
| `--fail-if-total-packages-exceeds=X`| Fail if total composer packages exceed X (exit code `3`)                    |
| `--no-ansi`                         | Disable ANSI coloring in output (e.g. for plain CI logs)                    |

🟢 **Exit codes:**

- `0` → OK: All checks passed
- `1` → Too many unused packages
- `2` → Outdated dependency violation
- `3` → Total number of packages exceeds defined limit

---

## ✅ Example

```bash
php bin/phpdi audit \
  --output=report.md \
  --output-json=report.json \
  --threshold=2 \
  --max-outdated=5 \
  --fail-if-total-packages-exceeds=100 \
  --exit-on-unused \
  --exit-on-outdated=minor
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
      - run: php bin/phpdi audit --exit-on-unused --exit-on-outdated=major --threshold=0 --no-ansi
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
    - php bin/phpdi audit --exit-on-unused --exit-on-outdated=major --threshold=0 --no-ansi
```

---

## 🧠 Notes

- `--no-ansi` is useful in CI to avoid special characters in log output.
- Output files (`.md` or `.json`) can be committed to track changes across pull requests.
- Designed to be used as a lightweight linter or metric in automation and review processes.