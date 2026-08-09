<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class DestructiveSchemaRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if ($operation->target === 'blueprint' && $operation->hasMethod('change')) {
            return $this->finding(
                $operation,
                'alter-column',
                Severity::Error,
                'Changing an existing column can rewrite or lock the table.',
                'Use an expand-and-contract migration and backfill data separately.',
            );
        }

        if (!in_array($operation->method, MethodCatalog::DESTRUCTIVE_METHODS, true)) {
            return null;
        }

        return $this->finding(
            $operation,
            'destructive-schema-change',
            Severity::Error,
            sprintf('The %s operation is destructive or may require an exclusive lock.', $operation->displayName()),
            'Split the rollout into backward-compatible steps and perform destructive cleanup later.',
        );
    }
}
