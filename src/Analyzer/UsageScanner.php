<?php

namespace PhpDependencyInspector\Analyzer;

use PhpDependencyInspector\Util\FileSearch;

class UsageScanner
{
    public function scan(string $path): array
    {
        $files = (new FileSearch())
            ->in($path)
            ->name('*.php')
            ->get();

        $namespaces = [];

        foreach ($files as $filePath) {
            $contents = file_get_contents($filePath);

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


    public function scanOld(string $path): array
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
