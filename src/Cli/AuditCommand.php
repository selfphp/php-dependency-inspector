<?php

namespace PhpDependencyInspector\Cli;

use Selfphp\Console\Contract\CommandInterface;
use PhpDependencyInspector\Analyzer\UsageScanner;
use PhpDependencyInspector\Composer\PackageLoader;
use PhpDependencyInspector\Composer\OutdatedPackageChecker;
use PhpDependencyInspector\Audit\AuditResult;
use PhpDependencyInspector\Audit\OutdatedPackage;

class AuditCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'audit';
    }

    public function getDescription(): string
    {
        return 'Performs an automated audit of Composer dependencies (for cron/CI).';
    }

    public function run(array $args): int
    {
        echo "[audit] Starting audit ...\n";

        // Default values
        $path = getcwd();
        $outputFile = null;
        $outputJson = null;
        $threshold = 0;
        $failOnUnused = false;
        $failOnOutdated = 'none';
        $maxOutdated = null;
        $maxTotalPackages = null;

        // Very basic argument parsing
        foreach ($args as $i => $arg) {
            if ($arg === '--path' && isset($args[$i + 1])) {
                $path = $args[$i + 1];
            }
            if ($arg === '--output' && isset($args[$i + 1])) {
                $outputFile = $args[$i + 1];
            }
            if ($arg === '--output-json' && isset($args[$i + 1])) {
                $outputJson = $args[$i + 1];
            }
            if ($arg === '--threshold' && isset($args[$i + 1])) {
                $threshold = (int) $args[$i + 1];
            }
            if ($arg === '--exit-on-unused') {
                $failOnUnused = true;
            }
            if ($arg === '--exit-on-outdated' && isset($args[$i + 1])) {
                $failOnOutdated = $args[$i + 1];
            }
            if ($arg === '--max-outdated' && isset($args[$i + 1]) && is_numeric($args[$i + 1])) {
                $maxOutdated = (int) $args[$i + 1];
            }
            if ($arg === '--fail-if-total-packages-exceeds' && isset($args[$i + 1]) && is_numeric($args[$i + 1])) {
                $maxTotalPackages = (int) $args[$i + 1];
            }
        }

        if (!is_dir($path)) {
            echo "[error] Invalid path: $path\n";
            return 1;
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
            $checker = new OutdatedPackageChecker();
            $outdated = $checker->getOutdatedPackages();

            foreach ($outdated as $entry) {
                $audit->outdatedPackages[] = new OutdatedPackage(
                    $entry['name'],
                    $entry['version'],
                    $entry['latest']
                );
            }
        } catch (\Throwable $e) {
            echo "[warn] Could not retrieve outdated packages: " . $e->getMessage() . "\n";
        }

        // Console summary
        echo "✔ Used: " . count($audit->usedPackages) . "\n";
        echo "⚠ Unused: " . count($audit->unusedPackages) . "\n";
        echo "🔺 Outdated: " . count($audit->outdatedPackages) . "\n";

        // Optional markdown output
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
            $report .= "\n## 🔺 Outdated Packages\n";
            foreach ($audit->outdatedPackages as $pkg) {
                $report .= "- `{$pkg->name}` ({$pkg->currentVersion} → {$pkg->latestVersion})\n";
            }
            file_put_contents($outputFile, $report);
            echo "[✓] Markdown report saved to: $outputFile\n";
        }

        // Optional JSON output
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
            file_put_contents($outputJson, json_encode($jsonData, JSON_PRETTY_PRINT));
            echo "[✓] JSON report saved to: $outputJson\n";
        }

        // Outdated summary
        $major = array_filter($audit->outdatedPackages, fn($p) => $p->isMajorUpdate());
        $minor = array_filter($audit->outdatedPackages, fn($p) => !$p->isMajorUpdate() && $p->isMinorUpdate());

        if (!empty($major)) {
            echo "\n🔺 Major updates available:\n";
            foreach ($major as $pkg) {
                echo " - {$pkg->name} {$pkg->currentVersion} → {$pkg->latestVersion}\n";
            }
        }

        if (!empty($minor)) {
            echo "\n⚠ Minor updates available:\n";
            foreach ($minor as $pkg) {
                echo " - {$pkg->name} {$pkg->currentVersion} → {$pkg->latestVersion}\n";
            }
        }

        if ($maxOutdated !== null && count($audit->outdatedPackages) > $maxOutdated) {
            echo "[✘] Too many outdated packages (" . count($audit->outdatedPackages) . "), limit: $maxOutdated\n";
            return 2;
        }

        if ($maxTotalPackages !== null && count($packages) > $maxTotalPackages) {
            echo "[✘] Too many total packages (" . count($packages) . "), limit: $maxTotalPackages\n";
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
