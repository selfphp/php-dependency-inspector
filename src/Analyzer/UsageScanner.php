<?php

namespace PhpDependencyInspector\Analyzer;

use Symfony\Component\Finder\Finder;

class UsageScanner
{
    public function scan(string $path): array
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        $namespaces = [];

        foreach ($finder as $file) {
            $contents = $file->getContents();

            // Match namespace definitions
            if (preg_match('/namespace\s+([^;]+);/', $contents, $nsMatch)) {
                $namespaces[] = trim($nsMatch[1], '\\');
            }

            // Match usage (use, new, extends, implements)
            if (preg_match_all('/(?:use|new|extends|implements)\s+([\\\\\w]+)/', $contents, $matches)) {
                foreach ($matches[1] as $match) {
                    $namespaces[] = trim($match, '\\');
                }
            }
        }

        return array_unique($namespaces);
    }
}
