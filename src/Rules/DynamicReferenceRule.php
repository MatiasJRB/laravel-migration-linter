<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class DynamicReferenceRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if (!$operation->dynamicTable) {
            return null;
        }

        return $this->finding(
            $operation,
            'dynamic-table-name',
            Severity::Error,
            'A dynamic table name cannot be mapped to hot-table policy or reviewed deterministically.',
            'Use a literal table name or explicitly ignore this rule after manual review.',
        );
    }
}
