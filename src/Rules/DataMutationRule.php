<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class DataMutationRule implements Rule
{
    use CreatesFinding;

    /** @var list<string> */
    private const METHODS = [
        'decrement',
        'decrementeach',
        'delete',
        'increment',
        'incrementeach',
        'insert',
        'insertgetid',
        'insertorignore',
        'insertorignorereturning',
        'insertorignoreusing',
        'insertusing',
        'truncate',
        'update',
        'updatefrom',
        'updateorinsert',
        'upsert',
    ];

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if ($operation->target !== 'db' || !in_array($operation->method, self::METHODS, true)) {
            return null;
        }

        return $this->finding(
            $operation,
            'data-mutation',
            Severity::Error,
            'Data mutation inside up() can make deployments slow, non-atomic, or difficult to resume.',
            'Run the backfill as an idempotent, observable command before enforcing the new schema.',
        );
    }
}
