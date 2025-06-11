# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] – 2025-06-11

### Changed
- `PackageLoader` now automatically excludes `require-dev` packages from analysis
- Dev-only dependencies like `phpunit/phpunit` are no longer falsely reported as unused
- Autoload namespaces from `packages-dev` in `composer.lock` are now ignored

### Improved
- More accurate audit results by filtering out development-only packages

### Fixed
- Incorrect detection of unused dev dependencies (e.g., test frameworks)


## [1.3.0] – 2025-06-11

### Changed
- Removed `symfony/finder` as dependency

### Added
- Introduced custom `FileSearch` utility for recursive file listing and filtering

### Improved
- `UsageScanner` now uses internal `FileSearch` logic
- Codebase is now free of all Symfony components
- Improved cross-platform compatibility and reduced external dependency footprint


## [1.2.0] – 2025-06-11

### Changed
- Replaced `symfony/console` with lightweight `selfphp/console`
- Updated `bin/phpdi` to use custom `ConsoleApp` class
- CLI command handling now fully managed via internal console layer

### Added
- Internal command registration via `register()` method
- Compatibility layer for PSR-4-style commands with custom dispatcher

### Removed
- Dependency on `symfony/console` (`^6.0`) – reducing external requirements


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

