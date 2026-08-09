<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class RequiredColumnRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if (
            $operation->target !== 'blueprint'
            || $operation->context !== 'alter'
            || !in_array($operation->method, MethodCatalog::COLUMN_METHODS, true)
            || in_array($operation->method, MethodCatalog::COLUMNS_WITHOUT_REQUIRED_DEFAULT, true)
            || $operation->hasMethod('foreign', 'constrained', 'references')
            || $operation->hasMethod('change')
            || $operation->hasMethod('storedas', 'virtualas')
            || $operation->hasMethod('nullable', 'default', 'usecurrent')
        ) {
            return null;
        }

        return $this->finding(
            $operation,
            'required-column-without-default',
            Severity::Error,
            'Adding a required column without a default is not backward-compatible with existing rows and old application code.',
            'Add the column as nullable, deploy compatible code, backfill it, then enforce the constraint separately.',
        );
    }
}
