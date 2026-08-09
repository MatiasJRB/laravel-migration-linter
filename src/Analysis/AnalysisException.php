<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Analysis;

use RuntimeException;

final class AnalysisException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $sourceLine = null,
    ) {
        parent::__construct($message);
    }
}
