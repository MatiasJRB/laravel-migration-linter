<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Config;

use InvalidArgumentException;

final readonly class Configuration
{
    /**
     * @param list<string> $paths
     * @param list<string> $hotTables
     * @param list<string> $ignoredRules
     * @param list<string> $allowedBlueprintMethods
     */
    public function __construct(
        public array $paths = ['database/migrations'],
        public array $hotTables = [],
        public array $ignoredRules = [],
        public array $allowedBlueprintMethods = [],
    ) {
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $knownKeys = ['paths', 'hot_tables', 'ignore_rules', 'allowed_blueprint_methods'];
        $unknownKeys = array_diff(array_keys($values), $knownKeys);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException('Unknown configuration key(s): '.implode(', ', $unknownKeys));
        }

        return new self(
            paths: self::stringList($values['paths'] ?? ['database/migrations'], 'paths'),
            hotTables: self::normalizedList($values['hot_tables'] ?? [], 'hot_tables'),
            ignoredRules: self::normalizedList($values['ignore_rules'] ?? [], 'ignore_rules'),
            allowedBlueprintMethods: self::normalizedList(
                $values['allowed_blueprint_methods'] ?? [],
                'allowed_blueprint_methods',
            ),
        );
    }

    public function isHotTable(?string $table): bool
    {
        return $table !== null && in_array(strtolower($table), $this->hotTables, true);
    }

    public function ignoresRule(string $ruleId): bool
    {
        return in_array(strtolower($ruleId), $this->ignoredRules, true);
    }

    public function allowsBlueprintMethod(string $method): bool
    {
        return in_array(strtolower($method), $this->allowedBlueprintMethods, true);
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private static function stringList(mixed $value, string $key): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Configuration key "%s" must be an array.', $key));
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_string($item) || trim($item) === '') {
                throw new InvalidArgumentException(sprintf('Configuration key "%s" must contain non-empty strings.', $key));
            }

            $result[] = trim($item);
        }

        return array_values(array_unique($result));
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private static function normalizedList(mixed $value, string $key): array
    {
        return array_map('strtolower', self::stringList($value, $key));
    }
}
