<?php

namespace PhpDependencyInspector\Composer;

class OutdatedPackageChecker
{
    /**
     * Runs `composer outdated` and returns outdated packages.
     *
     * @return array<int, array<string, string>>
     */
    public function getOutdatedPackages(): array
    {
        $composerBin = $this->findComposerBinary();

        $cmd = [$composerBin, 'outdated', '--format=json'];

        $descriptorSpec = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($cmd, $descriptorSpec, $pipes, getcwd());

        if (!\is_resource($process)) {
            throw new \RuntimeException('Failed to start composer process.');
        }

        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \RuntimeException("composer outdated failed: " . trim($errorOutput));
        }

        $data = json_decode($output, true);
        return $data['installed'] ?? [];
    }

    /**
     * Tries to locate the composer binary (global or local).
     *
     * @return string
     */
    private function findComposerBinary(): string
    {
        // Plattformunabhängiges binary-finden
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $whichCmd = $isWindows ? 'where' : 'which';

        $paths = shell_exec($whichCmd . ' composer');

        if ($paths) {
            $lines = array_filter(array_map('trim', explode(PHP_EOL, $paths)));
            foreach ($lines as $line) {
                if (is_file($line) && is_executable($line)) {
                    return $line;
                }
            }
        }

        // Fallback: composer.phar im Projektordner
        $localPhar = getcwd() . '/composer.phar';
        if (file_exists($localPhar)) {
            return PHP_BINARY . ' ' . escapeshellarg($localPhar);
        }

        throw new \RuntimeException('Composer could not be found. Please make sure it is installed and accessible.');
    }

    /**
     * Optional helper: Render Markdown table
     */
    public function renderMarkdownReport(array $packages): string
    {
        if (empty($packages)) {
            return "No outdated packages found.";
        }

        $lines = [
            "# Outdated Packages Report\n",
            "| Package | Current | Latest | Status |",
            "|---------|---------|--------|--------|"
        ];

        foreach ($packages as $pkg) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s |',
                $pkg['name'],
                $pkg['version'],
                $pkg['latest'],
                $pkg['latest-status'] ?? 'unknown'
            );
        }

        return implode("\n", $lines);
    }
}
