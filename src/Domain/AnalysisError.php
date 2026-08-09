<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Domain;

final readonly class AnalysisError
{
    public function __construct(
        public string $message,
        public ?string $file = null,
        public ?int $line = null,
    ) {
    }

    /** @return array{message: string, file: string|null, line: int|null} */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }
}
