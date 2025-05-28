<?php

namespace PhpDependencyInspector\Audit;

class OutdatedPackage
{
    public string $name;
    public string $currentVersion;
    public string $latestVersion;

    public function __construct(string $name, string $currentVersion, string $latestVersion)
    {
        $this->name = $name;
        $this->currentVersion = $currentVersion;
        $this->latestVersion = $latestVersion;
    }

    public function isMajorUpdate(): bool
    {
        return explode('.', $this->currentVersion)[0] !== explode('.', $this->latestVersion)[0];
    }

    public function isMinorUpdate(): bool
    {
        return explode('.', $this->currentVersion)[0] === explode('.', $this->latestVersion)[0]
            && explode('.', $this->currentVersion)[1] !== explode('.', $this->latestVersion)[1];
    }
}
