<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Tests\Command;

use MatiasJRB\LaravelMigrationLinter\MigrationLinterApplication;
use MatiasJRB\LaravelMigrationLinter\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class LintCommandTest extends TestCase
{
    public function test_command_uses_configured_paths_and_json_output(): void
    {
        $this->writeMigration(<<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name');
        });
    }
};
PHP);

        file_put_contents(
            $this->projectDirectory.'/.laravel-migration-linter.php',
            "<?php return ['paths' => ['database/migrations']];\n",
        );

        $originalDirectory = getcwd();
        chdir($this->projectDirectory);

        try {
            $command = (new MigrationLinterApplication())->find('lint');
            $tester = new CommandTester($command);
            $exitCode = $tester->execute(['--format' => 'json']);
        } finally {
            if ($originalDirectory !== false) {
                chdir($originalDirectory);
            }
        }

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('"status": "unsafe"', $tester->getDisplay());
    }

    public function test_command_writes_markdown_report(): void
    {
        $this->lint(<<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name')->nullable();
        });
PHP);

        $originalDirectory = getcwd();
        chdir($this->projectDirectory);

        try {
            $command = (new MigrationLinterApplication())->find('lint');
            $tester = new CommandTester($command);
            $exitCode = $tester->execute([
                'paths' => ['database/migrations'],
                '--format' => 'markdown',
                '--output' => 'build/report.md',
            ]);
        } finally {
            if ($originalDirectory !== false) {
                chdir($originalDirectory);
            }
        }

        self::assertSame(0, $exitCode);
        self::assertFileExists($this->projectDirectory.'/build/report.md');
        self::assertStringContainsString('**SAFE**', (string) file_get_contents($this->projectDirectory.'/build/report.md'));
    }
}
