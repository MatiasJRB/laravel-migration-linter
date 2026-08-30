<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Analysis;

use MatiasJRB\LaravelMigrationLinter\Domain\Operation;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeFinder;

final class OperationExtractor
{
    /** @var list<string> */
    private const SCHEMA_READ_METHODS = [
        'getalltables',
        'getallviews',
        'getcolumnlisting',
        'getcolumns',
        'getcolumntype',
        'getconnection',
        'getcurrentschemalisting',
        'getcurrentschemaname',
        'getforeignkeys',
        'getindexlisting',
        'getindexes',
        'getschemas',
        'gettablelisting',
        'gettables',
        'gettypes',
        'getviews',
        'hascolumn',
        'hascolumns',
        'hasforeignkey',
        'hasindex',
        'hastable',
        'hasview',
    ];

    /** @var list<string> */
    private const DB_READ_METHODS = ['scalar', 'select', 'selectone', 'selectresultsets'];

    public function __construct(private readonly string $file)
    {
    }

    /**
     * @param array<Node\Stmt> $statements
     * @return list<Operation>
     */
    public function extract(array $statements): array
    {
        $finder = new NodeFinder();
        $operations = [];

        foreach ($finder->findInstanceOf($statements, StaticCall::class) as $call) {
            if ($this->isChainedCall($call)) {
                continue;
            }

            $class = $this->className($call);
            $method = $this->methodName($call->name);
            if ($class === null || $method === null) {
                continue;
            }

            if ($class === 'schema') {
                array_push($operations, ...$this->extractSchemaCall($method, $call->args, $call->getStartLine()));
            } elseif ($class === 'db') {
                array_push($operations, ...$this->extractDbStaticCall($method, $call->args, $call->getStartLine()));
            }
        }

        foreach ($finder->findInstanceOf($statements, MethodCall::class) as $call) {
            if ($this->isNestedMethodCall($call)) {
                continue;
            }

            [$base, $chain] = $this->methodChain($call);
            if (!$base instanceof StaticCall || $chain === []) {
                continue;
            }

            $class = $this->className($base);
            $baseMethod = $this->methodName($base->name);
            if ($class === 'schema' && $baseMethod === 'connection') {
                $schemaCall = $chain[0];
                array_push(
                    $operations,
                    ...$this->extractSchemaCall($schemaCall['name'], $schemaCall['args'], $schemaCall['line']),
                );
            } elseif ($class === 'db') {
                array_push($operations, ...$this->extractDbChain($baseMethod, $base->args, $chain));
            }
        }

        return $operations;
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $args
     * @return list<Operation>
     */
    private function extractSchemaCall(string $method, array $args, int $line): array
    {
        if (in_array($method, self::SCHEMA_READ_METHODS, true) || $method === 'connection') {
            return [];
        }

        if (!in_array($method, ['create', 'table'], true)) {
            return [$this->operation('schema', $method, [$method], 'migration', $this->stringArgument($args, 0), false, $line)];
        }

        $table = $this->stringArgument($args, 0);
        $context = $method === 'create' ? 'create' : 'alter';
        $operations = [];

        if ($table === null) {
            $operations[] = $this->operation('schema', $method, [$method], $context, null, true, $line);
        }

        $callback = $args[1]->value ?? null;
        if (!$callback instanceof Closure) {
            $operations[] = $this->operation(
                'schema',
                'dynamiccallback',
                ['dynamiccallback'],
                $context,
                $table,
                false,
                $line,
            );

            return $operations;
        }

        array_push($operations, ...$this->extractBlueprintCalls($callback, $table, $context));

        return $operations;
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $args
     * @return list<Operation>
     */
    private function extractDbStaticCall(string $method, array $args, int $line): array
    {
        if (in_array($method, self::DB_READ_METHODS, true) || in_array($method, ['connection', 'table', 'transaction'], true)) {
            return [];
        }

        return [$this->operation('db', $method, [$method], 'migration', null, false, $line)];
    }

    /**
     * @param string|null $baseMethod
     * @param array<Arg|VariadicPlaceholder> $baseArgs
     * @param list<array{name: string, args: array<Arg|VariadicPlaceholder>, line: int}> $chain
     * @return list<Operation>
     */
    private function extractDbChain(?string $baseMethod, array $baseArgs, array $chain): array
    {
        $table = $baseMethod === 'table' ? $this->stringArgument($baseArgs, 0) : null;
        $operations = [];

        foreach ($chain as $call) {
            if (in_array($call['name'], self::DB_READ_METHODS, true) || in_array($call['name'], ['where', 'orderby', 'limit', 'lock'], true)) {
                continue;
            }

            if (in_array($call['name'], [
                'decrement',
                'decrementeach',
                'delete',
                'increment',
                'incrementeach',
                'insert',
                'insertgetid',
                'insertorignore',
                'insertorignorereturning',
                'insertorignoreusing',
                'insertusing',
                'statement',
                'truncate',
                'unprepared',
                'update',
                'updatefrom',
                'updateorinsert',
                'upsert',
            ], true)) {
                $operations[] = $this->operation(
                    'db',
                    $call['name'],
                    [$call['name']],
                    'migration',
                    $table,
                    $baseMethod === 'table' && $table === null,
                    $call['line'],
                );
            }
        }

        return $operations;
    }

    /** @return list<Operation> */
    private function extractBlueprintCalls(Closure $callback, ?string $table, string $context): array
    {
        $parameter = $callback->params[0]->var ?? null;
        if (!$parameter instanceof Expr\Variable || !is_string($parameter->name)) {
            return [$this->operation('schema', 'dynamiccallback', ['dynamiccallback'], $context, $table, false, $callback->getStartLine())];
        }

        $finder = new NodeFinder();
        $operations = [];

        foreach ($finder->findInstanceOf($callback->stmts, MethodCall::class) as $call) {
            if ($this->isNestedMethodCall($call)) {
                continue;
            }

            [$base, $chain] = $this->methodChain($call);
            if (!$base instanceof Expr\Variable || $base->name !== $parameter->name || $chain === []) {
                continue;
            }

            $methodNames = array_column($chain, 'name');
            $operations[] = $this->operation(
                'blueprint',
                $methodNames[0],
                $methodNames,
                $context,
                $table,
                false,
                $chain[0]['line'],
            );
        }

        return $operations;
    }

    private function isChainedCall(StaticCall $call): bool
    {
        $parent = $call->getAttribute('parent');

        return $parent instanceof MethodCall && $parent->var === $call;
    }

    private function isNestedMethodCall(MethodCall $call): bool
    {
        $parent = $call->getAttribute('parent');

        return $parent instanceof MethodCall && $parent->var === $call;
    }

    /**
     * @return array{Node\Expr, list<array{name: string, args: array<Arg|VariadicPlaceholder>, line: int}>}
     */
    private function methodChain(MethodCall $call): array
    {
        $chain = [];
        $cursor = $call;

        while ($cursor instanceof MethodCall) {
            $name = $this->methodName($cursor->name);
            if ($name === null) {
                return [$cursor, []];
            }

            array_unshift($chain, [
                'name' => $name,
                'args' => $cursor->args,
                'line' => $cursor->getStartLine(),
            ]);
            $cursor = $cursor->var;
        }

        return [$cursor, $chain];
    }

    private function className(StaticCall $call): ?string
    {
        if (!$call->class instanceof Node\Name) {
            return null;
        }

        return strtolower($call->class->getLast());
    }

    private function methodName(Node $node): ?string
    {
        return $node instanceof Identifier ? strtolower($node->toString()) : null;
    }

    /** @param array<Arg|VariadicPlaceholder> $args */
    private function stringArgument(array $args, int $index): ?string
    {
        $argument = $args[$index] ?? null;
        $value = $argument instanceof Arg ? $argument->value : null;

        return $value instanceof String_ ? $value->value : null;
    }

    /** @param list<string> $chain */
    private function operation(
        string $target,
        string $method,
        array $chain,
        string $context,
        ?string $table,
        bool $dynamicTable,
        int $line,
    ): Operation {
        return new Operation(
            target: $target,
            method: $method,
            chain: $chain,
            context: $context,
            table: $table,
            dynamicTable: $dynamicTable,
            file: $this->file,
            line: $line,
        );
    }
}
