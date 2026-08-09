<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class ForeignKeyRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if (
            $operation->target !== 'blueprint'
            || $operation->context !== 'alter'
            || !$operation->hasMethod('foreign', 'constrained', 'references')
        ) {
            return null;
        }

        return $this->finding(
            $operation,
            $configuration->isHotTable($operation->table) ? 'hot-table-foreign-key' : 'foreign-key-operation',
            Severity::Error,
            'Adding a foreign key validates existing rows and may lock both referenced tables.',
            'Add and validate the constraint with an online, database-specific rollout after cleaning existing data.',
        );
    }
}
