<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Support;

final class Path
{
    public static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
