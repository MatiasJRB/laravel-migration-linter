<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Config;

use InvalidArgumentException;
use MatiasJRB\LaravelMigrationLinter\Support\Path;

final class ConfigurationLoader
{
    public const DEFAULT_FILENAME = '.laravel-migration-linter.php';

    public function load(?string $path, string $workingDirectory): Configuration
    {
        $resolvedPath = $path === null
            ? $workingDirectory.DIRECTORY_SEPARATOR.self::DEFAULT_FILENAME
            : $this->resolvePath($path, $workingDirectory);

        if ($path === null && !is_file($resolvedPath)) {
            return new Configuration();
        }

        if (!is_file($resolvedPath) || !is_readable($resolvedPath)) {
            throw new InvalidArgumentException(sprintf('Configuration file "%s" is not readable.', $resolvedPath));
        }

        $values = (static function (string $configPath): mixed {
            return require $configPath;
        })($resolvedPath);

        if (!is_array($values)) {
            throw new InvalidArgumentException('The configuration file must return an array.');
        }

        /** @var array<string, mixed> $values */
        return Configuration::fromArray($values);
    }

    private function resolvePath(string $path, string $workingDirectory): string
    {
        if (Path::isAbsolute($path)) {
            return $path;
        }

        return $workingDirectory.DIRECTORY_SEPARATOR.$path;
    }
}
