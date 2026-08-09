<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

trait CreatesFinding
{
    private function finding(
        Operation $operation,
        string $ruleId,
        Severity $severity,
        string $message,
        string $suggestion,
    ): Finding {
        return new Finding(
            ruleId: $ruleId,
            severity: $severity,
            message: $message,
            suggestion: $suggestion,
            file: $operation->file,
            line: $operation->line,
            table: $operation->table,
            operation: $operation->displayName(),
        );
    }
}
