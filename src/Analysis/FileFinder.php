<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Analysis;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use MatiasJRB\LaravelMigrationLinter\Support\Path;
use SplFileInfo;

final class FileFinder
{
    /**
     * @param list<string> $paths
     * @return list<string>
     */
    public function find(array $paths, string $workingDirectory): array
    {
        if ($paths === []) {
            throw new InvalidArgumentException('At least one migration path is required.');
        }

        $files = [];

        foreach ($paths as $path) {
            $resolved = $this->resolvePath($path, $workingDirectory);

            if (is_file($resolved)) {
                if (strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) === 'php') {
                    $files[] = $this->canonicalPath($resolved);
                }

                continue;
            }

            if (!is_dir($resolved)) {
                throw new InvalidArgumentException(sprintf('Migration path "%s" does not exist.', $path));
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($resolved, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $files[] = $this->canonicalPath($file->getPathname());
            }
        }

        $files = array_values(array_unique($files));
        sort($files);

        if ($files === []) {
            throw new InvalidArgumentException('No PHP migration files were found in the configured paths.');
        }

        return $files;
    }

    public function displayPath(string $path, string $workingDirectory): string
    {
        $root = rtrim($this->canonicalPath($workingDirectory), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $canonicalPath = $this->canonicalPath($path);

        if (str_starts_with($canonicalPath, $root)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($canonicalPath, strlen($root)));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $canonicalPath);
    }

    private function resolvePath(string $path, string $workingDirectory): string
    {
        if (Path::isAbsolute($path)) {
            return $path;
        }

        return $workingDirectory.DIRECTORY_SEPARATOR.$path;
    }

    private function canonicalPath(string $path): string
    {
        return realpath($path) ?: $path;
    }
}
