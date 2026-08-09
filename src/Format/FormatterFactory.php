<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Format;

use InvalidArgumentException;

final class FormatterFactory
{
    public function create(string $format): Formatter
    {
        return match (strtolower($format)) {
            'console' => new ConsoleFormatter(),
            'json' => new JsonFormatter(),
            'markdown' => new MarkdownFormatter(),
            default => throw new InvalidArgumentException(
                sprintf('Unknown format "%s". Expected console, json, or markdown.', $format),
            ),
        };
    }
}
