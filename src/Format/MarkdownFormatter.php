<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Format;

use MatiasJRB\LaravelMigrationLinter\Domain\LintResult;

final class MarkdownFormatter implements Formatter
{
    public function render(LintResult $result): string
    {
        $icon = match ($result->status()) {
            'safe' => '✅',
            'unsafe' => '⚠️',
            default => '❌',
        };

        $lines = [
            '# Laravel Migration Linter',
            '',
            sprintf('%s **%s** — scanned %d migration file(s).', $icon, strtoupper($result->status()), $result->filesScanned),
            '',
        ];

        if ($result->errors !== []) {
            $lines[] = '## Analysis errors';
            $lines[] = '';
            foreach ($result->errors as $error) {
                $location = $error->file ?? 'configuration';
                if ($error->line !== null) {
                    $location .= ':'.$error->line;
                }

                $lines[] = sprintf('- `%s`: %s', self::escape($location), self::escape($error->message));
            }
            $lines[] = '';
        }

        if ($result->findings === []) {
            $lines[] = $result->errors === []
                ? 'No unsafe migration operations were detected.'
                : 'No safety findings were produced because analysis did not complete.';

            return implode(PHP_EOL, $lines).PHP_EOL;
        }

        $lines[] = '## Findings';
        $lines[] = '';
        $lines[] = '| Severity | Location | Rule | Table | Operation | Message |';
        $lines[] = '| --- | --- | --- | --- | --- | --- |';

        foreach ($result->findings as $finding) {
            $lines[] = sprintf(
                '| %s | `%s:%d` | `%s` | %s | `%s` | %s<br>**Safer approach:** %s |',
                strtoupper($finding->severity->value),
                self::escape($finding->file),
                $finding->line,
                self::escape($finding->ruleId),
                self::escape($finding->table ?? '—'),
                self::escape($finding->operation),
                self::escape($finding->message),
                self::escape($finding->suggestion),
            );
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private static function escape(string $value): string
    {
        return str_replace(['|', "\n", "\r"], ['\\|', ' ', ' '], $value);
    }
}
