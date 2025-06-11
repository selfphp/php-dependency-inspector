# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] – 2025-06-11

### Added
- `ComposerHelper` for platform-independent execution of Composer via `proc_open`
- Support for `COMPOSER_BIN` environment variable
- Markdown report rendering for `OutdatedPackageChecker`

### Improved
- Outdated package analysis is now cross-platform compatible (Windows, Linux, macOS)
- Refactored `OutdatedPackageChecker` to rely on internal helpers instead of direct process calls


## [1.0.0] - 2025-05-30
### Added
- Initial release of `php-dependency-inspector`
- Command `analyse` to detect unused Composer packages
- Command `audit` for CI integration with threshold & exit codes
- Markdown and JSON report output
- Detection of major/minor updates
- Exit codes:
    - `1` for unused packages exceeding threshold
    - `2` for outdated packages policy violation
- Option: `--no-ansi` for CI-compatible output
- Option: `--max-outdated` to limit outdated packages
- Option: `--fail-if-total-packages-exceeds` to enforce package count limits
- Color-coded console output for outdated packages (major/minor)
- Basic GitHub/GitLab CI examples in README

