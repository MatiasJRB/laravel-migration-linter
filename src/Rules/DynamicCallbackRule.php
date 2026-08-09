<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class DynamicCallbackRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if ($operation->target !== 'schema' || $operation->method !== 'dynamiccallback') {
            return null;
        }

        return $this->finding(
            $operation,
            'dynamic-schema-callback',
            Severity::Error,
            'The Schema callback is not an inline closure and cannot be analyzed statically.',
            'Use an inline closure or explicitly ignore this rule after reviewing the callback implementation.',
        );
    }
}
