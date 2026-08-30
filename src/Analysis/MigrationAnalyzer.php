<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Analysis;

use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionMethod;

final class MigrationAnalyzer
{
    private readonly Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? self::createParser();
    }

    private static function createParser(): Parser
    {
        $factory = new ParserFactory();
        $reflection = new ReflectionClass($factory);

        if ($reflection->hasMethod('createForNewestSupportedVersion')) {
            $parser = $reflection->getMethod('createForNewestSupportedVersion')->invoke($factory);
        } else {
            // PHP-Parser 4 exposes create() and PREFER_PHP7, while PHP-Parser 5
            // replaced both with createForNewestSupportedVersion(). Reflection
            // keeps both branches analyzable regardless of the installed major.
            $create = new ReflectionMethod($factory, 'create');
            $mode = constant(ParserFactory::class . '::PREFER_PHP7');
            $parser = $create->invoke($factory, $mode);
        }

        if (!$parser instanceof Parser) {
            throw new \LogicException('PHP-Parser factory did not return a parser instance.');
        }

        return $parser;
    }

    /** @return list<Operation> */
    public function analyze(string $file, string $displayPath): array
    {
        $source = file_get_contents($file);
        if ($source === false) {
            throw new AnalysisException(sprintf('Unable to read migration file "%s".', $displayPath));
        }

        try {
            $nodes = $this->parser->parse($source);
        } catch (Error $error) {
            throw new AnalysisException(
                sprintf('PHP parse error: %s', $error->getRawMessage()),
                $error->getStartLine(),
            );
        }

        if ($nodes === null) {
            throw new AnalysisException('The migration file is empty.');
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new ParentConnectingVisitor());
        $nodes = $traverser->traverse($nodes);

        $finder = new NodeFinder();
        $classes = $finder->findInstanceOf($nodes, Class_::class);
        $upMethods = [];

        foreach ($classes as $class) {
            if (!$this->isMigrationClass($class)) {
                continue;
            }

            $up = $class->getMethod('up');
            if ($up !== null) {
                $upMethods[] = $up;
            }
        }

        if ($upMethods === []) {
            throw new AnalysisException('No Laravel migration up() method was found.');
        }

        $extractor = new OperationExtractor($displayPath);
        $operations = [];

        foreach ($upMethods as $upMethod) {
            if ($upMethod->stmts === null) {
                continue;
            }

            array_push($operations, ...$extractor->extract($upMethod->stmts));
        }

        return $operations;
    }

    private function isMigrationClass(Class_ $class): bool
    {
        if (!$class->extends instanceof Node\Name) {
            return false;
        }

        return strtolower($class->extends->getLast()) === 'migration';
    }
}
