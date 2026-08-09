<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Tests\Config;

use InvalidArgumentException;
use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    public function test_values_are_normalized(): void
    {
        $configuration = Configuration::fromArray([
            'paths' => ['database/migrations'],
            'hot_tables' => ['Orders', 'USERS'],
            'ignore_rules' => ['RAW-SQL'],
            'allowed_blueprint_methods' => ['TenantColumns'],
        ]);

        self::assertTrue($configuration->isHotTable('orders'));
        self::assertTrue($configuration->ignoresRule('raw-sql'));
        self::assertTrue($configuration->allowsBlueprintMethod('tenantcolumns'));
    }

    /** @param array<string, mixed> $values */
    #[DataProvider('invalidConfigurations')]
    public function test_invalid_configuration_is_rejected(array $values): void
    {
        $this->expectException(InvalidArgumentException::class);
        Configuration::fromArray($values);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidConfigurations(): iterable
    {
        yield 'unknown key' => [['unknown' => true]];
        yield 'non-array list' => [['hot_tables' => 'users']];
        yield 'empty entry' => [['paths' => ['']]];
    }
}
