<?php

namespace PhpDependencyInspector\Util;

class FileSearch
{
    private array $files = [];

    public function in(string $path): static
    {
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($rii as $file) {
            if ($file->isFile()) {
                $this->files[] = $file->getPathname();
            }
        }

        return $this;
    }

    public function name(string $pattern): static
    {
        $this->files = array_filter($this->files, function ($file) use ($pattern) {
            return fnmatch($pattern, basename($file));
        });

        return $this;
    }

    public function get(): array
    {
        return $this->files;
    }
}
