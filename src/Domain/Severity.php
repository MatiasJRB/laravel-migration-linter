<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Domain;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
