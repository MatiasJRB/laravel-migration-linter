<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Domain;

final readonly class Finding
{
    public function __construct(
        public string $ruleId,
        public Severity $severity,
        public string $message,
        public string $suggestion,
        public string $file,
        public int $line,
        public ?string $table,
        public string $operation,
    ) {
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'rule' => $this->ruleId,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'suggestion' => $this->suggestion,
            'file' => $this->file,
            'line' => $this->line,
            'table' => $this->table,
            'operation' => $this->operation,
        ];
    }
}
