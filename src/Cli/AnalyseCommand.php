<?php

namespace PhpDependencyInspector\Cli;

use Selfphp\Console\Contract\CommandInterface;
use PhpDependencyInspector\Analyzer\UsageScanner;
use PhpDependencyInspector\Composer\PackageLoader;

class AnalyseCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'analyse';
    }

    public function getDescription(): string
    {
        return 'Analyzes which Composer packages are actually used in your codebase.';
    }

    public function run(array $args): int
    {
        echo "[analyse] Starting dependency analysis ...\n";

        $path = getcwd();
        $onlyUnused = false;
        $outputFile = null;

        // Primitive Argument Parsing
        foreach ($args as $i => $arg) {
            if ($arg === '--path' && isset($args[$i + 1])) {
                $path = $args[$i + 1];
            }
            if ($arg === '--only-unused') {
                $onlyUnused = true;
            }
            if ($arg === '--output' && isset($args[$i + 1])) {
                $outputFile = $args[$i + 1];
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

        $unused = [];
        $used = [];

        foreach ($packages as $package => $namespaces) {
            $isUsed = false;
            foreach ($namespaces as $ns) {
                if ($this->isNamespaceUsed($ns, $usedNamespaces)) {
                    $isUsed = true;
                    break;
                }
            }

            if ($isUsed) {
                $used[] = $package;
                if (!$onlyUnused) {
                    echo sprintf("%-35s ✔ used\n", $package);
                }
            } else {
                $unused[] = $package;
                echo sprintf("%-35s ⚠ unused\n", $package);
            }
        }

        if (!$onlyUnused) {
            echo "\nSummary: ✔ " . count($used) . " used, ⚠ " . count($unused) . " unused\n";
        }

        if ($outputFile) {
            $report = "# Dependency Analysis Report\n\n";
            if (!$onlyUnused) {
                $report .= "## ✔ Used Packages\n";
                foreach ($used as $p) {
                    $report .= "- $p\n";
                }
                $report .= "\n";
            }

            $report .= "## ⚠ Unused Packages\n";
            foreach ($unused as $p) {
                $report .= "- $p\n";
            }

            $report .= "\n---\n\nTotal: " . (count($used) + count($unused)) . " packages\n";
            $report .= "✔ Used: " . count($used) . "\n";
            $report .= "⚠ Unused: " . count($unused) . "\n";

            file_put_contents($outputFile, $report);
            echo "\n[info] Report saved to: $outputFile\n";
        }

        return 0;
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
