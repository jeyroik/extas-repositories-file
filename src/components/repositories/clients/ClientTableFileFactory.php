<?php
namespace extas\components\repositories\clients;

use extas\interfaces\repositories\clients\IClientTableFileFactory;
use extas\interfaces\repositories\clients\IClientTable;

class ClientTableFileFactory implements IClientTableFileFactory
{
    public static function create(string $extension, $path, $tableName): IClientTable
    {
        $factoryRecipePath = getenv("EXTAS__FILE_RECIPE_PATH") ?: __DIR__ . '/../../../../resources/recipe.json';
        $factoryRecipe = json_decode(file_get_contents($factoryRecipePath), true);

        if (!isset($factoryRecipe[$extension])) {
            throw new MissedOrUnknown('Extension "' . $extension . '"');
        }

        return new $factoryRecipe[$extension]($path, $tableName);
    }
}
