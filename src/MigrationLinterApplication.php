<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter;

use MatiasJRB\LaravelMigrationLinter\Command\LintCommand;
use Symfony\Component\Console\Application;

final class MigrationLinterApplication extends Application
{
    public function __construct()
    {
        parent::__construct('Laravel Migration Linter', Version::VERSION);
        $this->addCommands([new LintCommand()]);
        $this->setDefaultCommand('lint', true);
    }
}
