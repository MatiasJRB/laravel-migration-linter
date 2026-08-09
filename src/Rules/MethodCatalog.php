<?php

declare(strict_types=1);

namespace MatiasJRB\LaravelMigrationLinter\Rules;

final class MethodCatalog
{
    /** @var list<string> */
    public const COLUMN_METHODS = [
        'addcolumn',
        'bigincrements',
        'biginteger',
        'binary',
        'boolean',
        'char',
        'computed',
        'date',
        'datetime',
        'datetimetz',
        'datetimes',
        'decimal',
        'double',
        'enum',
        'float',
        'foreignid',
        'foreignidfor',
        'foreignulid',
        'foreignulidfor',
        'foreignuuid',
        'foreignuuidfor',
        'geography',
        'geometry',
        'id',
        'increments',
        'integer',
        'integerincrements',
        'ipaddress',
        'json',
        'jsonb',
        'longtext',
        'macaddress',
        'mediumincrements',
        'mediuminteger',
        'mediumtext',
        'morphs',
        'nullablemorphs',
        'nullablenumericmorphs',
        'nullabletimestamps',
        'nullabletimestampstz',
        'nullableulidmorphs',
        'nullableuuidmorphs',
        'numericmorphs',
        'remembertoken',
        'rawcolumn',
        'set',
        'smallincrements',
        'smallinteger',
        'softdeletes',
        'softdeletesdatetime',
        'softdeletestz',
        'string',
        'text',
        'time',
        'timestamp',
        'timestamptz',
        'timestamps',
        'timestampstz',
        'timetz',
        'tinyincrements',
        'tinyinteger',
        'tinytext',
        'tsvector',
        'ulid',
        'ulidmorphs',
        'unsignedbiginteger',
        'unsignedinteger',
        'unsignedmediuminteger',
        'unsignedsmallinteger',
        'unsignedtinyinteger',
        'uuid',
        'uuidmorphs',
        'vector',
        'year',
    ];

    /** @var list<string> */
    public const COLUMNS_WITHOUT_REQUIRED_DEFAULT = [
        'computed',
        'datetimes',
        'nullablemorphs',
        'nullablenumericmorphs',
        'nullabletimestamps',
        'nullabletimestampstz',
        'nullableulidmorphs',
        'nullableuuidmorphs',
        'rawcolumn',
        'remembertoken',
        'softdeletes',
        'softdeletesdatetime',
        'softdeletestz',
        'timestamps',
        'timestampstz',
    ];

    /** @var list<string> */
    public const INDEX_METHODS = [
        'fulltext',
        'index',
        'morphs',
        'nullablemorphs',
        'nullablenumericmorphs',
        'nullableulidmorphs',
        'nullableuuidmorphs',
        'numericmorphs',
        'primary',
        'rawindex',
        'spatialindex',
        'ulidmorphs',
        'unique',
        'uuidmorphs',
        'vectorindex',
    ];

    /** @var list<string> */
    public const FOREIGN_METHODS = ['foreign'];

    /** @var list<string> */
    public const DESTRUCTIVE_METHODS = [
        'disableforeignkeyconstraints',
        'drop',
        'dropcolumn',
        'dropcolumns',
        'dropconstrainedforeignid',
        'dropconstrainedforeignidfor',
        'dropforeign',
        'dropforeignidfor',
        'dropfulltext',
        'dropifexists',
        'dropindex',
        'dropmorphs',
        'dropprimary',
        'dropremembertoken',
        'dropsoftdeletes',
        'dropsoftdeletestz',
        'dropspatialindex',
        'droptimestamps',
        'droptimestampstz',
        'dropunique',
        'dropalltables',
        'dropalltypes',
        'dropallviews',
        'dropdatabaseifexists',
        'enableforeignkeyconstraints',
        'rename',
        'renamecolumn',
        'renameindex',
        'withoutforeignkeyconstraints',
    ];

    /** @var list<string> */
    public const TABLE_OPTION_METHODS = ['charset', 'collation', 'comment', 'engine', 'innodb', 'temporary'];

    /** @var list<string> */
    public const COLUMN_MODIFIERS = [
        'after',
        'always',
        'autoincrement',
        'change',
        'charset',
        'collation',
        'comment',
        'constrained',
        'default',
        'first',
        'from',
        'generatedas',
        'index',
        'invisible',
        'nullable',
        'on',
        'ondelete',
        'onupdate',
        'primary',
        'references',
        'storedas',
        'unsigned',
        'usecurrent',
        'usecurrentonupdate',
        'virtualas',
        'unique',
    ];

    public static function isKnownBlueprintMethod(string $method): bool
    {
        if ($method === 'after') {
            return true;
        }

        return in_array($method, self::COLUMN_METHODS, true)
            || in_array($method, self::INDEX_METHODS, true)
            || in_array($method, self::FOREIGN_METHODS, true)
            || in_array($method, self::DESTRUCTIVE_METHODS, true)
            || in_array($method, self::TABLE_OPTION_METHODS, true);
    }
}
