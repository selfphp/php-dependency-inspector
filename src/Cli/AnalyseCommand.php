<?php

namespace PhpDependencyInspector\Cli;

use PhpDependencyInspector\Analyzer\UsageScanner;
use PhpDependencyInspector\Composer\PackageLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyseCommand extends Command
{
    protected static $defaultName = 'analyse';
    protected static $defaultDescription = 'Analyzes which Composer packages are actually used in your codebase.';

    protected function configure(): void
    {
        $this
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the project root (defaults to current working directory)',
                getcwd()
            )
            ->addOption(
                'only-unused',
                null,
                InputOption::VALUE_NONE,
                'Show only packages that are not used in the codebase'
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to save a markdown report to (optional)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>\u{1F50D} Starting dependency analysis...</info>');

        $path = $input->getOption('path');
        if (!is_dir($path)) {
            $output->writeln('<error>\u{274C} Invalid path specified.</error>');
            return Command::FAILURE;
        }

        $onlyUnused = $input->getOption('only-unused');
        $outputFile = $input->getOption('output');

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
                    $output->writeln(sprintf('%-35s <info>\u2714 used</info>', $package));
                }
            } else {
                $unused[] = $package;
                $output->writeln(sprintf('%-35s <comment>\u26A0 unused</comment>', $package));
            }
        }

        if (!$onlyUnused) {
            $output->writeln("\nSummary: <info>" . count($used) . " used</info>, <comment>" . count($unused) . " unused</comment>");
        }

        // Output file if specified
        if ($outputFile) {
            $report = "# Dependency Analysis Report\n\n";

            if (!$onlyUnused) {
                $report .= "## \u2714 Used Packages\n";
                foreach ($used as $p) {
                    $report .= "- $p\n";
                }
                $report .= "\n";
            }

            $report .= "## \u26A0 Unused Packages\n";
            foreach ($unused as $p) {
                $report .= "- $p\n";
            }

            $report .= "\n---\n\nTotal: " . (count($used) + count($unused)) . " packages\n";
            $report .= "\u2714 Used: " . count($used) . "\n";
            $report .= "\u26A0 Unused: " . count($unused) . "\n";

            file_put_contents($outputFile, $report);
            $output->writeln("\n<info>Report saved to: $outputFile</info>");
        }

        return Command::SUCCESS;
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
