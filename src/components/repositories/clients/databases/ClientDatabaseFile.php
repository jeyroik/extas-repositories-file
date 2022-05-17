<?php
namespace extas\components\repositories\clients\databases;

use extas\interfaces\repositories\clients\IClientDatabase;
use extas\interfaces\repositories\clients\IClientTable;
use extas\components\repositories\clients\ClientTableFile;

/**
 * Class ClientDatabaseFile
 *
 * @package extas\components\repositories\clients
 * @author jeyroik@gmail.com
 */
class ClientDatabaseFile implements IClientDatabase
{
    /**
     * @var string[]
     */
    protected static array $dbs = [];

    /**
     * @var array[]
     */
    protected static array $tables = [];

    /**
     * @var string[]
     */
    protected static array $extensions = [];

    /**
     * @var string
     */
    protected string $curDB = '';

    /**
     * ClientDatabaseFile constructor.
     *
     * @param string $client
     * @param string $name
     */
    public function __construct(string $dsn, string $name, $string $extension)
    {
        if (!isset(static::$dbs[$name])) {
            static::$dbs[$name] = $dsn;
            static::$extensions[$name] = $extension;
        }

        $this->curDB = $name;
    }

    /**
     * @param string $tableName
     *
     * @return IClientTable
     * @throws
     */
    public function getTable(string $tableName): IClientTable
    {
        if (!isset(static::$tables[$this->curDB . '.' . $tableName])) {
            static::$tables[$this->curDB . '.' . $tableName] = ClientTableFactory::create(
                static::$extensions[$this->curDB], static::$dbs[$this->curDB], $tableName
            );
        }

        return static::$tables[$this->curDB . '.' . $tableName];
    }
}
