<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Format;

use MatiasJRB\LaravelMigrationLinter\Domain\LintResult;

interface Formatter
{
    public function render(LintResult $result): string;
}
