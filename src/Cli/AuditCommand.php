<?php

namespace PhpDependencyInspector\Cli;

use PhpDependencyInspector\Analyzer\UsageScanner;
use PhpDependencyInspector\Composer\OutdatedPackageChecker;
use PhpDependencyInspector\Composer\PackageLoader;
use PhpDependencyInspector\Audit\AuditResult;
use PhpDependencyInspector\Audit\OutdatedPackage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuditCommand extends Command
{
    protected static $defaultName = 'audit';
    protected static $defaultDescription = 'Performs an automated audit of Composer dependencies (for cron/CI).';

    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to the project root (defaults to current working directory)', getcwd())
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Path to save a markdown report to (optional)')
            ->addOption('output-json', null, InputOption::VALUE_REQUIRED, 'Path to save a JSON report to (optional)')
            ->addOption('threshold', null, InputOption::VALUE_REQUIRED, 'Threshold for unused packages to trigger failure (optional)', 0)
            ->addOption('exit-on-unused', null, InputOption::VALUE_NONE, 'Exit with code 1 if unused packages exceed threshold')
            ->addOption('exit-on-outdated', null, InputOption::VALUE_REQUIRED, 'Exit with code 2 if outdated packages found (none, minor, major)', 'none')
            ->addOption('max-outdated', null, InputOption::VALUE_REQUIRED, 'Maximum number of outdated packages allowed before failing (optional)')
            ->addOption('fail-if-total-packages-exceeds', null, InputOption::VALUE_REQUIRED, 'Fail if total composer packages exceed this number');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path');
        $outputFile = $input->getOption('output');
        $outputJson = $input->getOption('output-json');
        $threshold = (int) $input->getOption('threshold');
        $failOnUnused = $input->getOption('exit-on-unused');
        $failOnOutdated = $input->getOption('exit-on-outdated');
        $maxOutdated = $input->getOption('max-outdated');
        $maxOutdated = is_numeric($maxOutdated) ? (int) $maxOutdated : null;
        $maxTotalPackages = $input->getOption('fail-if-total-packages-exceeds');
        $maxTotalPackages = is_numeric($maxTotalPackages) ? (int) $maxTotalPackages : null;

        if (!$output->isDecorated()) {
            $output->getFormatter()->setDecorated(false);
        }

        if (!is_dir($path)) {
            $output->writeln('❌ Invalid path specified.');
            return Command::FAILURE;
        }

        $packageLoader = new PackageLoader();
        $packages = $packageLoader->loadPackages();

        $scanner = new UsageScanner();
        $usedNamespaces = $scanner->scan($path);

        $audit = new AuditResult();

        foreach ($packages as $package => $namespaces) {
            $isUsed = false;
            foreach ($namespaces as $ns) {
                if ($this->isNamespaceUsed($ns, $usedNamespaces)) {
                    $isUsed = true;
                    break;
                }
            }
            if ($isUsed) {
                $audit->usedPackages[] = $package;
            } else {
                $audit->unusedPackages[] = $package;
            }
        }

        try {
            $outdatedChecker = new OutdatedPackageChecker();
            $rawOutdated = $outdatedChecker->getOutdatedPackages();
            foreach ($rawOutdated as $entry) {
                $audit->outdatedPackages[] = new OutdatedPackage(
                    $entry['name'],
                    $entry['version'],
                    $entry['latest']
                );
            }
        } catch (\Throwable $e) {
            $output->writeln("⚠ Could not retrieve outdated packages: {$e->getMessage()}");
        }

        if ($outputFile) {
            $report = "# Dependency Audit Report\n\n";
            $report .= "## ✅ Used Packages\n";
            foreach ($audit->usedPackages as $p) {
                $report .= "- $p\n";
            }
            $report .= "\n## ⚠ Unused Packages\n";
            foreach ($audit->unusedPackages as $p) {
                $report .= "- $p\n";
            }
            $report .= "\n## 🚫 Outdated Packages\n";
            foreach ($audit->outdatedPackages as $pkg) {
                $report .= "- `{$pkg->name}` ({$pkg->currentVersion} → {$pkg->latestVersion})\n";
            }

            file_put_contents($outputFile, $report);
        }

        if ($outputJson) {
            $jsonData = [
                'used' => $audit->usedPackages,
                'unused' => $audit->unusedPackages,
                'outdated' => array_map(fn($p) => [
                    'name' => $p->name,
                    'currentVersion' => $p->currentVersion,
                    'latestVersion' => $p->latestVersion,
                ], $audit->outdatedPackages),
            ];
            file_put_contents($outputJson, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $major = array_filter($audit->outdatedPackages, fn($p) => $p->isMajorUpdate());
        $minor = array_filter($audit->outdatedPackages, fn($p) => !$p->isMajorUpdate() && $p->isMinorUpdate());

        if (!empty($major)) {
            $output->writeln($output->isDecorated() ? "\n<fg=red>🔺 Major updates available:</>" : "\nMAJOR updates available:");
            foreach ($major as $pkg) {
                $output->writeln(" - {$pkg->name} {$pkg->currentVersion} → {$pkg->latestVersion}");
            }
        }

        if (!empty($minor)) {
            $output->writeln($output->isDecorated() ? "\n<fg=yellow>⚠ Minor updates available:</>" : "\nMINOR updates available:");
            foreach ($minor as $pkg) {
                $output->writeln(" - {$pkg->name} {$pkg->currentVersion} → {$pkg->latestVersion}");
            }
        }

        if ($maxOutdated !== null && count($audit->outdatedPackages) > $maxOutdated) {
            $output->writeln("❌ Too many outdated packages: " . count($audit->outdatedPackages) . " (max allowed: $maxOutdated)");
            return 2;
        }

        if ($maxTotalPackages !== null && count($packages) > $maxTotalPackages) {
            $output->writeln("❌ Too many total packages: " . count($packages) . " (max allowed: $maxTotalPackages)");
            return 3;
        }

        return $audit->getExitCode($failOnUnused, $failOnOutdated, $threshold);
    }

    private function isNamespaceUsed(string $namespace, array $usedNamespaces): bool
    {
        foreach ($usedNamespaces as $used) {
            if (str_starts_with($used, $namespace) || str_starts_with($namespace, $used)) {
                return true;
            }
        }
        return false;
    }
}
