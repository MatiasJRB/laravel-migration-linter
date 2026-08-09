<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class TableOptionRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if (
            $operation->target !== 'blueprint'
            || $operation->context !== 'alter'
            || !in_array($operation->method, MethodCatalog::TABLE_OPTION_METHODS, true)
        ) {
            return null;
        }

        return $this->finding(
            $operation,
            'table-option-change',
            Severity::Warning,
            'Changing table options may rebuild or lock the existing table.',
            'Review the generated DDL for your database and run the change with an online schema tool when necessary.',
        );
    }
}
