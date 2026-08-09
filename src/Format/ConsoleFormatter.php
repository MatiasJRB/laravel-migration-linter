<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Format;

use MatiasJRB\LaravelMigrationLinter\Domain\LintResult;

final class ConsoleFormatter implements Formatter
{
    public function render(LintResult $result): string
    {
        $lines = [strtoupper($result->status())];

        foreach ($result->errors as $error) {
            $location = $error->file ?? 'configuration';
            if ($error->line !== null) {
                $location .= ':'.$error->line;
            }

            $lines[] = sprintf('  ERROR %s — %s', $location, $error->message);
        }

        foreach ($result->findings as $finding) {
            $table = $finding->table === null ? '' : sprintf(' table=%s', $finding->table);
            $lines[] = sprintf(
                '  %s %s:%d [%s]%s %s',
                strtoupper($finding->severity->value),
                $finding->file,
                $finding->line,
                $finding->ruleId,
                $table,
                $finding->message,
            );
            $lines[] = '    Suggestion: '.$finding->suggestion;
        }

        $lines[] = sprintf(
            'Scanned %d file(s): %d finding(s), %d error(s).',
            $result->filesScanned,
            count($result->findings),
            count($result->errors),
        );

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
