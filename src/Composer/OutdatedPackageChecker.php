<?php

namespace PhpDependencyInspector\Composer;

use PhpDependencyInspector\Support\ComposerHelper;

class OutdatedPackageChecker
{
    /**
     * Runs `composer outdated` and returns outdated packages.
     *
     * @return array<int, array<string, string>>
     */
    public function getOutdatedPackages(): array
    {
        // Composer-Befehl über Helper ausführen
        $result = ComposerHelper::run(['outdated', '--format=json']);

        // Ergebnis dekodieren
        $data = json_decode($result['output'], true);

        if (!is_array($data) || !isset($data['installed'])) {
            throw new \RuntimeException("Unexpected composer output.");
        }

        return $data['installed'];
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
