<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Format;

use JsonException;
use MatiasJRB\LaravelMigrationLinter\Domain\LintResult;
use MatiasJRB\LaravelMigrationLinter\Version;

final class JsonFormatter implements Formatter
{
    /** @throws JsonException */
    public function render(LintResult $result): string
    {
        return json_encode(
            [
                'schema_version' => '1',
                'tool_version' => Version::VERSION,
                'status' => $result->status(),
                'files_scanned' => $result->filesScanned,
                'findings' => array_map(static fn ($finding): array => $finding->toArray(), $result->findings),
                'errors' => array_map(static fn ($error): array => $error->toArray(), $result->errors),
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
    }
}
