<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Domain;

final readonly class LintResult
{
    /**
     * @param list<Finding> $findings
     * @param list<AnalysisError> $errors
     */
    public function __construct(
        public int $filesScanned,
        public array $findings,
        public array $errors = [],
    ) {
    }

    public function status(): string
    {
        if ($this->errors !== []) {
            return 'error';
        }

        return $this->findings === [] ? 'safe' : 'unsafe';
    }

    public function exitCode(): int
    {
        return match ($this->status()) {
            'safe' => 0,
            'unsafe' => 1,
            default => 2,
        };
    }
}
