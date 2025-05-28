<?php

namespace PhpDependencyInspector\Audit;

class AuditResult
{
    /** @var string[] */
    public array $unusedPackages = [];

    /** @var OutdatedPackage[] */
    public array $outdatedPackages = [];

    public function getExitCode(bool $failOnUnused, string $failOnOutdated, int $threshold): int
    {
        $exitCode = 0;

        if ($failOnUnused && count($this->unusedPackages) > $threshold) {
            $exitCode = 1;
        }

        if ($failOnOutdated !== 'none') {
            foreach ($this->outdatedPackages as $pkg) {
                if (
                    ($failOnOutdated === 'major' && $pkg->isMajorUpdate()) ||
                    ($failOnOutdated === 'minor' && ($pkg->isMajorUpdate() || $pkg->isMinorUpdate()))
                ) {
                    $exitCode = max($exitCode, 2);
                }
            }
        }

        return $exitCode;
    }

}
