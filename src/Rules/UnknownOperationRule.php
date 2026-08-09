<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;

final class UnknownOperationRule implements Rule
{
    use CreatesFinding;

    /** @var list<string> */
    private const KNOWN_SCHEMA_METHODS = [
        'create',
        'disableforeignkeyconstraints',
        'drop',
        'dropalltables',
        'dropalltypes',
        'dropallviews',
        'dropcolumns',
        'dropdatabaseifexists',
        'dropifexists',
        'dynamiccallback',
        'enableforeignkeyconstraints',
        'rename',
        'table',
        'withoutforeignkeyconstraints',
    ];

    /** @var list<string> */
    private const KNOWN_DB_METHODS = [
        'affectingstatement',
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
        'statement',
        'truncate',
        'unprepared',
        'update',
        'updatefrom',
        'updateorinsert',
        'upsert',
    ];

    public function inspect(Operation $operation, Configuration $configuration): ?Finding
    {
        if ($operation->target === 'blueprint') {
            if (
                MethodCatalog::isKnownBlueprintMethod($operation->method)
                || $configuration->allowsBlueprintMethod($operation->method)
            ) {
                return null;
            }
        } elseif ($operation->target === 'schema' && in_array($operation->method, self::KNOWN_SCHEMA_METHODS, true)) {
            return null;
        } elseif ($operation->target === 'db' && in_array($operation->method, self::KNOWN_DB_METHODS, true)) {
            return null;
        }

        return $this->finding(
            $operation,
            'unknown-operation',
            Severity::Error,
            sprintf('The %s operation is not in the built-in safety model.', $operation->method),
            'Review it manually, then add the Blueprint macro to allowed_blueprint_methods or ignore this rule explicitly.',
        );
    }
}
