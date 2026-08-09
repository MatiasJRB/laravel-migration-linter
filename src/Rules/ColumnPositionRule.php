<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class ColumnPositionRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if ($operation->target !== 'blueprint' || $operation->context !== 'alter' || !$operation->hasMethod('after', 'first')) {
            return null;
        }

        return $this->finding(
            $operation,
            'column-position',
            Severity::Warning,
            'Reordering columns can force a table rewrite and is not portable across database engines.',
            'Append the column without after() or first(); column order should not be an application contract.',
        );
    }
}
