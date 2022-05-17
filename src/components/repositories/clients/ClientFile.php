<?php
namespace extas\components\repositories\clients;

use extas\components\repositories\clients\databases\ClientDatabaseFile;

/**
 * Class ClientFle
 *
 * @package extas\components\repositories\clients
 * @author jeyroik@gmail.com
 */
class ClientFile extends Client
{
    protected static FIELD__EXT = 'ext';
    protected static FIELD__PATH = 'path';

    protected static array $instances = [];

    protected string $clientName = 'file';
    protected string $dbFileExtension = '';

    /**
     * Client constructor.
     *
     * @param $dsn
     * @throws MissedOrUnknown
     */
    public function __construct($dsn)
    {
        if (empty($dsn)) {
            throw new MissedOrUnknown('dsn');
        }

        if (is_array($dsn)) {
            $this->dsn = $dsn[static::FIELD__PATH] ?? '';
            $this->dbFileExtension = $dsn[static::FIELD__EXT] ?? '';
        } else {
            $dsnAsArray = explode('.', $dsn);
            $this->dbFileExtension = array_pop($dsnAsArray);
            $this->dsn = implode('.', $dsnAsArray);
        }
    }

    /**
     * @param $dbName
     *
     * @return mixed
     * @throws
     */
    public function getDb($dbName)
    {
        $key = $this->dsn . '/' . $dbName . $this->dbFileExtension;

        return isset(static::$instances[$key])
            ? static::$instances[$key]
            : static::$instances[$key] = new ClientDatabaseFile($this->dsn, $dbName, $this->dbFileExtension);
    }
}
