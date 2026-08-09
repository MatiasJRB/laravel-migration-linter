<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Domain;

final readonly class Operation
{
    /** @var array<string, string> */
    private const DISPLAY_NAMES = [
        'dropcolumn' => 'dropColumn',
        'dropcolumns' => 'dropColumns',
        'dropconstrainedforeignid' => 'dropConstrainedForeignId',
        'dropconstrainedforeignidfor' => 'dropConstrainedForeignIdFor',
        'dropforeign' => 'dropForeign',
        'dropforeignidfor' => 'dropForeignIdFor',
        'dropfulltext' => 'dropFullText',
        'dropifexists' => 'dropIfExists',
        'dropindex' => 'dropIndex',
        'dropmorphs' => 'dropMorphs',
        'dropprimary' => 'dropPrimary',
        'dropremembertoken' => 'dropRememberToken',
        'dropsoftdeletes' => 'dropSoftDeletes',
        'dropsoftdeletestz' => 'dropSoftDeletesTz',
        'dropspatialindex' => 'dropSpatialIndex',
        'droptimestamps' => 'dropTimestamps',
        'droptimestampstz' => 'dropTimestampsTz',
        'dropunique' => 'dropUnique',
        'dropalltables' => 'dropAllTables',
        'dropalltypes' => 'dropAllTypes',
        'dropallviews' => 'dropAllViews',
        'dropdatabaseifexists' => 'dropDatabaseIfExists',
        'foreignid' => 'foreignId',
        'foreignidfor' => 'foreignIdFor',
        'foreignulid' => 'foreignUlid',
        'foreignulidfor' => 'foreignUlidFor',
        'foreignuuid' => 'foreignUuid',
        'foreignuuidfor' => 'foreignUuidFor',
        'insertorignorereturning' => 'insertOrIgnoreReturning',
        'insertorignoreusing' => 'insertOrIgnoreUsing',
        'nullabletimestampstz' => 'nullableTimestampsTz',
        'nullablemorphs' => 'nullableMorphs',
        'rawcolumn' => 'rawColumn',
        'renamecolumn' => 'renameColumn',
        'renameindex' => 'renameIndex',
        'softdeletes' => 'softDeletes',
        'softdeletesdatetime' => 'softDeletesDatetime',
        'softdeletestz' => 'softDeletesTz',
        'updatefrom' => 'updateFrom',
        'usecurrent' => 'useCurrent',
        'usecurrentonupdate' => 'useCurrentOnUpdate',
        'vectorindex' => 'vectorIndex',
    ];

    /**
     * @param list<string> $chain
     */
    public function __construct(
        public string $target,
        public string $method,
        public array $chain,
        public string $context,
        public ?string $table,
        public bool $dynamicTable,
        public string $file,
        public int $line,
    ) {
    }

    public function displayName(): string
    {
        $prefix = match ($this->target) {
            'blueprint' => '$table',
            'db' => 'DB',
            default => 'Schema',
        };

        $separator = $this->target === 'blueprint' ? '->' : '::';

        $chain = array_map(
            static fn (string $method): string => self::DISPLAY_NAMES[$method] ?? $method,
            $this->chain,
        );

        return $prefix.$separator.implode('->', $chain);
    }

    public function hasMethod(string ...$methods): bool
    {
        foreach ($methods as $method) {
            if (in_array(strtolower($method), $this->chain, true)) {
                return true;
            }
        }

        return false;
    }
}
