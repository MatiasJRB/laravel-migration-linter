<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class RawSqlRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        $rawDatabaseOperation = $operation->target === 'db'
            && in_array($operation->method, ['affectingstatement', 'statement', 'unprepared'], true);
        $rawBlueprintOperation = $operation->target === 'blueprint'
            && $operation->hasMethod('computed', 'rawcolumn', 'storedas', 'virtualas');

        if (!$rawDatabaseOperation && !$rawBlueprintOperation) {
            return null;
        }

        return $this->finding(
            $operation,
            'raw-sql',
            Severity::Error,
            'Raw SQL cannot be proven safe through static Blueprint analysis.',
            'Move data work to a resumable command or document and explicitly ignore this rule after review.',
        );
    }
}
