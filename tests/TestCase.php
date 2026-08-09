<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Tests;

use MatiasJRB\LaravelMigrationLinter\Analysis\Linter;
use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\LintResult;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected string $projectDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDirectory = sys_get_temp_dir().'/laravel-migration-linter-'.bin2hex(random_bytes(8));
        mkdir($this->projectDirectory.'/database/migrations', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDirectory);

        parent::tearDown();
    }

    protected function lint(string $upBody, ?Configuration $configuration = null, string $downBody = ''): LintResult
    {
        $source = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
{$upBody}
    }

    public function down(): void
    {
{$downBody}
    }
};
PHP;

        $this->writeMigration($source);

        return (new Linter())->lint(
            ['database/migrations'],
            $configuration ?? new Configuration(),
            $this->projectDirectory,
        );
    }

    protected function writeMigration(string $source, string $filename = '2026_01_01_000000_test.php'): string
    {
        $path = $this->projectDirectory.'/database/migrations/'.$filename;
        file_put_contents($path, $source);

        return $path;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
