<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class IndexRule implements Rule
{
    use CreatesFinding;

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if ($operation->target !== 'blueprint' || $operation->context !== 'alter') {
            return null;
        }

        $indexMethod = null;
        foreach (MethodCatalog::INDEX_METHODS as $method) {
            if ($operation->hasMethod($method)) {
                $indexMethod = $method;
                break;
            }
        }

        if ($indexMethod === null || $operation->hasMethod('foreign', 'constrained', 'references')) {
            return null;
        }

        $hotTable = $configuration->isHotTable($operation->table);

        return $this->finding(
            $operation,
            $hotTable ? 'hot-table-index' : 'index-operation',
            $hotTable ? Severity::Error : Severity::Warning,
            $hotTable
                ? 'Adding an index to a configured hot table can lock writes or saturate database I/O.'
                : 'Adding an index may lock or scan an existing table, depending on the database engine and table size.',
            'Use an online or concurrent index strategy supported by your database and monitor it separately.',
        );
    }
}
