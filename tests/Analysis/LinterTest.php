<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Tests\Analysis;

use MatiasJRB\LaravelMigrationLinter\Analysis\Linter;
use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\Severity;
use MatiasJRB\LaravelMigrationLinter\Tests\TestCase;

final class LinterTest extends TestCase
{
    public function test_new_table_is_safe(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });
PHP);

        self::assertSame('safe', $result->status());
        self::assertSame(0, $result->exitCode());
        self::assertSame([], $result->findings);
    }

    public function test_nullable_and_defaulted_columns_are_safe(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nickname')->nullable();
            $table->boolean('active')->default(false);
            $table->softDeletes();
        });
PHP);

        self::assertSame('safe', $result->status());
    }

    public function test_required_column_is_unsafe(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nickname');
        });
PHP);

        self::assertSame('unsafe', $result->status());
        self::assertSame(1, $result->exitCode());
        self::assertSame('required-column-without-default', $result->findings[0]->ruleId);
        self::assertSame('users', $result->findings[0]->table);
    }

    public function test_destructive_operations_and_change_are_reported_but_down_is_ignored(): void
    {
        $result = $this->lint(
            <<<'PHP'
        Schema::rename('users', 'customers');
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('email')->change();
            $table->dropColumn('legacy');
        });
PHP,
            downBody: <<<'PHP'
        Schema::dropIfExists('customers');
PHP,
        );

        self::assertSame(
            ['destructive-schema-change', 'alter-column', 'destructive-schema-change'],
            array_column($result->findings, 'ruleId'),
        );
    }

    public function test_index_on_normal_and_hot_table_uses_different_severity(): void
    {
        $normal = $this->lint(<<<'PHP'
        Schema::table('events', function (Blueprint $table): void {
            $table->index('created_at');
        });
PHP);
        $hot = $this->lint(
            <<<'PHP'
        Schema::table('events', function (Blueprint $table): void {
            $table->index('created_at');
        });
PHP,
            new Configuration(hotTables: ['events']),
        );

        self::assertSame('index-operation', $normal->findings[0]->ruleId);
        self::assertSame(Severity::Warning, $normal->findings[0]->severity);
        self::assertSame('hot-table-index', $hot->findings[0]->ruleId);
        self::assertSame(Severity::Error, $hot->findings[0]->severity);
    }

    public function test_foreign_key_is_reported_once(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });
PHP);

        self::assertCount(1, $result->findings);
        self::assertSame('foreign-key-operation', $result->findings[0]->ruleId);
    }

    public function test_explicit_foreign_key_is_not_also_unknown(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users');
        });
PHP);

        self::assertCount(1, $result->findings);
        self::assertSame('foreign-key-operation', $result->findings[0]->ruleId);
    }

    public function test_raw_sql_and_data_mutation_are_reported(): void
    {
        $result = $this->lint(<<<'PHP'
        DB::statement('ALTER TABLE users ADD INDEX users_email_idx (email)');
        DB::table('users')->where('active', false)->update(['active' => true]);
PHP);

        self::assertSame(['raw-sql', 'data-mutation'], array_column($result->findings, 'ruleId'));
    }

    public function test_current_query_builder_mutations_are_reported(): void
    {
        $result = $this->lint(<<<'PHP'
        DB::table('counters')->incrementEach(['views' => 1]);
        DB::table('events')->updateFrom(['processed' => true]);
        DB::table('users')->insertOrIgnoreReturning([['email' => 'test@example.test']]);
        DB::table('archive')->insertOrIgnoreUsing(['id'], DB::table('users')->select('id'));
PHP);

        self::assertCount(4, $result->findings);
        self::assertSame(
            ['data-mutation', 'data-mutation', 'data-mutation', 'data-mutation'],
            array_column($result->findings, 'ruleId'),
        );
    }

    public function test_raw_column_expressions_are_reported_without_duplicate_required_column_finding(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('orders', function (Blueprint $table): void {
            $table->computed('total', 'quantity * price');
            $table->rawColumn('search_vector', 'tsvector');
            $table->string('slug')->storedAs("lower(name)");
        });
PHP);

        self::assertSame(
            ['raw-sql', 'raw-sql', 'raw-sql'],
            array_column($result->findings, 'ruleId'),
        );
    }

    public function test_laravel_13_blueprint_helpers_are_classified(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('documents', function (Blueprint $table): void {
            $table->nullableTimestampsTz();
            $table->softDeletesDatetime();
            $table->foreignUuidFor(User::class)->constrained();
            $table->vectorIndex('embedding');
        });
PHP);

        self::assertSame(
            ['foreign-key-operation', 'index-operation'],
            array_column($result->findings, 'ruleId'),
        );
    }

    public function test_current_schema_reads_are_ignored_and_global_drops_are_destructive(): void
    {
        $reads = $this->lint(<<<'PHP'
        Schema::hasIndex('users', ['email']);
        Schema::hasForeignKey('orders', ['user_id']);
        Schema::getCurrentSchemaName();
PHP);
        $drops = $this->lint(<<<'PHP'
        Schema::dropAllViews();
        Schema::dropAllTypes();
        Schema::dropDatabaseIfExists('legacy');
PHP);

        self::assertSame('safe', $reads->status());
        self::assertSame(
            ['destructive-schema-change', 'destructive-schema-change', 'destructive-schema-change'],
            array_column($drops->findings, 'ruleId'),
        );
    }

    public function test_blueprint_after_callback_is_not_also_unknown(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->after('name', function (Blueprint $table): void {
                $table->string('nickname')->nullable();
            });
        });
PHP);

        self::assertCount(1, $result->findings);
        self::assertSame('column-position', $result->findings[0]->ruleId);
    }

    public function test_unknown_blueprint_macro_is_unsafe_unless_allowed(): void
    {
        $unsafe = $this->lint(<<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->tenantColumns();
        });
PHP);
        $allowed = $this->lint(
            <<<'PHP'
        Schema::table('users', function (Blueprint $table): void {
            $table->tenantColumns();
        });
PHP,
            new Configuration(allowedBlueprintMethods: ['tenantcolumns']),
        );

        self::assertSame('unknown-operation', $unsafe->findings[0]->ruleId);
        self::assertSame('safe', $allowed->status());
    }

    public function test_dynamic_table_name_and_callback_are_reported(): void
    {
        $result = $this->lint(<<<'PHP'
        $tableName = 'users';
        Schema::table($tableName, fn (Blueprint $table) => $table->string('name'));
PHP);

        self::assertSame(
            ['dynamic-schema-callback', 'dynamic-table-name'],
            array_column($result->findings, 'ruleId'),
        );
    }

    public function test_schema_connection_is_analyzed(): void
    {
        $result = $this->lint(<<<'PHP'
        Schema::connection('tenant')->table('users', function (Blueprint $table): void {
            $table->dropColumn('legacy');
        });
PHP);

        self::assertSame('destructive-schema-change', $result->findings[0]->ruleId);
    }

    public function test_named_migration_class_is_analyzed(): void
    {
        $this->writeMigration(<<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

final class AddNicknameToUsers extends Migration
{
    public function up(): void
    {
        DatabaseSchema::table('users', function (Blueprint $table): void {
            $table->string('nickname');
        });
    }
}
PHP);

        $result = (new Linter())->lint(
            ['database/migrations'],
            new Configuration(),
            $this->projectDirectory,
        );

        self::assertSame('required-column-without-default', $result->findings[0]->ruleId);
    }

    public function test_raw_sql_is_not_misreported_as_a_table_name(): void
    {
        $result = $this->lint(<<<'PHP'
        DB::statement('ALTER TABLE users DROP COLUMN legacy');
PHP);

        self::assertNull($result->findings[0]->table);
    }

    public function test_ignored_rule_is_not_reported(): void
    {
        $result = $this->lint(
            <<<'PHP'
        Schema::table('events', function (Blueprint $table): void {
            $table->index('created_at');
        });
PHP,
            new Configuration(ignoredRules: ['index-operation']),
        );

        self::assertSame('safe', $result->status());
    }

    public function test_parse_error_returns_tool_error(): void
    {
        $this->writeMigration('<?php return new class extends Migration { public function up(: void {} };');

        $result = (new Linter())->lint(
            ['database/migrations'],
            new Configuration(),
            $this->projectDirectory,
        );

        self::assertSame('error', $result->status());
        self::assertSame(2, $result->exitCode());
        self::assertStringContainsString('PHP parse error', $result->errors[0]->message);
    }

    public function test_missing_path_returns_tool_error(): void
    {
        $result = (new Linter())->lint(
            ['missing'],
            new Configuration(),
            $this->projectDirectory,
        );

        self::assertSame('error', $result->status());
        self::assertSame(0, $result->filesScanned);
    }
}
