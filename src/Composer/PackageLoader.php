<?php

namespace PhpDependencyInspector\Composer;

use PhpDependencyInspector\Exception\LockFileNotFoundException;

class PackageLoader
{
    public function loadPackages(): array
    {
        $lockPath = getcwd() . '/composer.lock';
        if (!file_exists($lockPath)) {
            throw new LockFileNotFoundException('composer.lock file not found.');
        }

        $data = json_decode(file_get_contents($lockPath), true);

        $packages = [];
        // only production dependencies
        foreach ($data['packages'] ?? [] as $package) {
            //  foreach (array_merge($data['packages'] ?? [], $data['packages-dev'] ?? []) as $package) {
            $name = $package['name'];
            $autoload = $package['autoload']['psr-4'] ?? [];

            $namespaces = array_keys($autoload);
            $packages[$name] = $namespaces;
        }

        return $packages;
    }
}
