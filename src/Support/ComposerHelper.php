<?php

namespace PhpDependencyInspector\Support;

/**
 * Utility class for safely finding and executing the Composer binary across platforms.
 */
final class ComposerHelper
{
    /**
     * Attempts to locate the Composer binary.
     *
     * Search order:
     * 1. Environment variable COMPOSER_BIN
     * 2. Windows: `where composer.bat`
     * 3. Unix-like: `which composer`
     *
     * @return string Absolute path or command name
     * @throws \RuntimeException If Composer binary cannot be found
     */
    public static function findComposerBinary(): string
    {
        // 1. Manuell gesetzter Pfad (Umgebungsvariable)
        $customPath = getenv('COMPOSER_BIN');
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        // 2. Suche mit 'where' (Windows)
        if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
            $paths = explode(PHP_EOL, shell_exec('where composer.bat') ?? '');
        } else {
            $paths = explode(PHP_EOL, shell_exec('which composer') ?? '');
        }

        foreach ($paths as $path) {
            $path = trim($path);
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('Composer binary not found. Try setting COMPOSER_BIN manually.');
    }

    /**
     * Runs the Composer command with the given arguments.
     *
     * @param array $args Command-line arguments (e.g. ['outdated', '--format=json'])
     * @return array{output: string, error: string} Output and error streams
     * @throws \RuntimeException If Composer could not be executed
     */
    public static function run(array $args): array
    {
        $composer = self::findComposerBinary();
        $cmd = array_merge([$composer], $args);

        $descriptors = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($cmd, $descriptors, $pipes, getcwd());

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start Composer process.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \RuntimeException("Composer call failed: " . trim($stderr));
        }

        return [
            'output' => $stdout,
            'error' => $stderr,
        ];
    }

}
