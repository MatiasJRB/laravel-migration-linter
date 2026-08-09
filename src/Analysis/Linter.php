<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Analysis;

use InvalidArgumentException;
use MatiasJRB\LaravelMigrationLinter\Config\Configuration;
use MatiasJRB\LaravelMigrationLinter\Domain\AnalysisError;
use MatiasJRB\LaravelMigrationLinter\Domain\Finding;
use MatiasJRB\LaravelMigrationLinter\Domain\LintResult;
use MatiasJRB\LaravelMigrationLinter\Rules\RuleRegistry;
use Throwable;

final readonly class Linter
{
    public function __construct(
        private FileFinder $fileFinder = new FileFinder(),
        private MigrationAnalyzer $analyzer = new MigrationAnalyzer(),
        private ?RuleRegistry $rules = null,
    ) {
    }

    /**
     * @param list<string> $paths
     */
    public function lint(array $paths, Configuration $configuration, string $workingDirectory): LintResult
    {
        $rules = $this->rules ?? RuleRegistry::defaults();

        try {
            $files = $this->fileFinder->find($paths, $workingDirectory);
        } catch (InvalidArgumentException $error) {
            return new LintResult(0, [], [new AnalysisError($error->getMessage())]);
        }

        $findings = [];
        $errors = [];

        foreach ($files as $file) {
            $displayPath = $this->fileFinder->displayPath($file, $workingDirectory);

            try {
                $operations = $this->analyzer->analyze($file, $displayPath);
            } catch (AnalysisException $error) {
                $errors[] = new AnalysisError($error->getMessage(), $displayPath, $error->sourceLine);
                continue;
            } catch (Throwable $error) {
                $errors[] = new AnalysisError('Unexpected analysis failure: '.$error->getMessage(), $displayPath);
                continue;
            }

            foreach ($operations as $operation) {
                array_push($findings, ...$rules->inspect($operation, $configuration));
            }
        }

        usort($findings, self::compareFindings(...));

        return new LintResult(count($files), $findings, $errors);
    }

    private static function compareFindings(Finding $left, Finding $right): int
    {
        return [$left->file, $left->line, $left->ruleId] <=> [$right->file, $right->line, $right->ruleId];
    }
}
