<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\Operation;

final readonly class RuleRegistry
{
    /** @param list<Rule> $rules */
    public function __construct(private array $rules)
    {
    }

    public static function defaults(): self
    {
        return new self([
            new DynamicReferenceRule(),
            new DynamicCallbackRule(),
            new DestructiveSchemaRule(),
            new RawSqlRule(),
            new DataMutationRule(),
            new ForeignKeyRule(),
            new RequiredColumnRule(),
            new IndexRule(),
            new ColumnPositionRule(),
            new TableOptionRule(),
            new UnknownOperationRule(),
        ]);
    }

    /** @return list<Finding> */
    public function inspect(Operation $operation, Configuration $configuration): array
    {
        $findings = [];

        foreach ($this->rules as $rule) {
            $finding = $rule->inspect($operation, $configuration);
            if ($finding === null || $configuration->ignoresRule($finding->ruleId)) {
                continue;
            }

            $findings[] = $finding;
        }

        return $findings;
    }
}
