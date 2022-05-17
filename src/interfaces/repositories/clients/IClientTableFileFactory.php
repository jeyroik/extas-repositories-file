<?php
namespace extas\interfaces\repositories\clients;

interface IClientTableFileFactory
{
    public static function create(string $extension, $string $path, $string $tableName): IClientTable;
}
