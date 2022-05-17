<?php
namespace extas\components\repositories\drivers;

use extas\components\repositories\clients\ClientFile;

/**
 * Class DriverFile
 *
 * @package extas\components\repositories\drivers
 * @author jeyroik@gmail.com
 */
class DriverFile extends Driver
{
    protected string $clientClass = ClientFile::class;
}
