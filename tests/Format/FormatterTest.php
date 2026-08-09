<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Tests\Format;

use MatiasJRB\LaravelMigrationLinter\Format\JsonFormatter;
use MatiasJRB\LaravelMigrationLinter\Format\MarkdownFormatter;
use MatiasJRB\LaravelMigrationLinter\Tests\TestCase;

final class FormatterTest extends TestCase
{
    public function test_json_format_is_machine_readable(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('legacy');
        });
PHP);

        /** @var array<string, mixed> $payload */
        $payload = json_decode((new JsonFormatter())->render($result), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('1', $payload['schema_version']);
        self::assertSame('unsafe', $payload['status']);
        self::assertSame('destructive-schema-change', $payload['findings'][0]['rule']);
    }

    public function test_markdown_format_contains_actionable_table(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name');
        });
PHP);

        $markdown = (new MarkdownFormatter())->render($result);

        self::assertStringContainsString('## Findings', $markdown);
        self::assertStringContainsString('required-column-without-default', $markdown);
        self::assertStringContainsString('Safer approach', $markdown);
    }
}
