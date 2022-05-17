<?php
namespace extas\components\repositories\clients;

use extas\interfaces\IItem;
use extas\interfaces\repositories\clients\IClientTable;
use League\Monga\Collection;
use League\Monga\Cursor;
use League\Monga\Query\Aggregation;
use League\Monga\Query\Find;
use League\Monga\Query\Group;
use League\Monga\Query\Where;

/**
 * Class ClientTableMongo
 *
 * @package extas\components\repositories\clients
 * @author jeyroik@gmail.com
 */
class ClientTableMongo extends ClientTableAbstract implements IClientTable
{
    /**
     * @var array
     */
    protected array $collection = [];
    protected array $conditionDispatchers = [];
    protected string $dbPath = '';
    protected string $tableName = '';
    protected string $groupBy = '';

    /**
     * ClientTableMongo constructor.
     *
     * @param $collection
     */
    public function __construct(string $path, string $name)
    {
        $this->dbPath = $path;
        $this->tableName = $name;

        $db = json_decode(file_get_contents($this->dbPath), true);

        $this->collection = $db[$this->tableName] ?? [];
    }

    /**
     * @param array|Where $query
     * @param int $offset
     * @param array $fields
     *
     * @return array|IItem|null
     * @throws
     */
    public function findOne(array $query = [], int $offset = 0, array $fields = [])
    {
        $this->prepareQuery($query);

        $record = [];

        foreach($this->collection as $item) {
            if ($this->setItemConditions($query, $item)->isConditionTrue()) {
                $record = $item;
                break;
            }
        }

        if ($record) {
            $itemClass = $this->getItemClass();
            return new $itemClass($record);
        }

        return $record;
    }

    /**
     * @param array|Where $query
     * @param int $limit
     * @param int $offset
     * @param array $orderBy
     * @param array $fields
     *
     * @return array|IItem[]
     * @throws
     */
    public function findAll(array $query = [], int $limit = 0, int $offset = 0, array $orderBy = [], array $fields = [])
    {
        /**
         * @var $recordsCursor Cursor
         */
        $this->prepareQuery($query);

        $itemClass = $this->getItemClass();
        $records = [];
        $skipped = 0;

        foreach($this->collection as $item) {
            if ($this->setItemConditions($query, $item)->isConditionTrue()) {
                if ($skipped < $offset) {
                    continue;
                }
                $records[] = new $itemClass($item);
                if (count($records) == $limit) {
                    break;
                }
            }
        }

        if (!empty($orderBy)) {
            list($field, $asc) = $orderBy;

            $byField = array_column($records, null, $field);

            if ($asc == 'ASC') {
                ksort($byField);
            } else {
                krsort($byField);
            }

            $records = array_values($byField);
        }

        return empty($this->$groupBy) ? $records : array_column($records, null, $this->groupBy);
    }

    /**
     * @param IItem $item
     *
     * @return IItem
     * @throws \Exception
     */
    public function insert($item)
    {
        $itemData = $item->__toArray();

        if ($this->getPk() == '_id') {
            $idGeneratorClass = getenv('EXTAS__FILE_JSON_ID_GENERATOR_CLASS') ?: '';
            $idGenerator = new $idGeneratorClass();
            $itemData['_id'] = $idGenerator->generate();
            $item['_id'] = $itemData['_id'];
        }

        $this->collection[$itemData[$this->getPk()]] = $itemData;

        if ($this->reloadDbFile() !== false) {
            return $item;
        }

        throw new \Exception('Can not insert a record');
    }

    /**
     * @param array $query
     * @param $data
     *
     * @return int
     */
    public function updateMany($query, $data)
    {
        $records = $this->findAll($query);

        foreach($records as $record) {
            foreach($data as $field => $value) {
                $record[$field] = $data[$field];
            }
            $this->resetItem($record);
        }

        return $this->reloadDbFile() !== false ? count($records) : 0;
    }

    /**
     * @param IItem $item
     *
     * @return bool
     * @throws
     */
    public function update($item): bool
    {
        $this->resetItem($item);

        return $this->reloadDbFile() !== false ? true : false;
    }

    /**
     * @param array $query
     *
     * @return int
     * @throws
     */
    public function deleteMany($query)
    {
        $records = $this->findAll($query);

        foreach($records as $record) {
            $this->removeItem($record);
        }

        return $this->reloadDbFile() !== false ? count($records) : 0;
    }

    /**
     * @param IItem $item
     *
     * @return bool
     * @throws
     */
    public function delete($item): bool
    {
        $this->removeItem($item);

        return $this->reloadDbFile() !== false ? true : false;
    }

    /**
     * @return bool
     */
    public function drop(): bool
    {
        $this->collection = [];

        $db = $this->getDbContent();
        unset($db[$this->tableName]);

        $this->setDbContent($db);

        return true;
    }

    /**
     * @param array $groupBy
     *
     * @return $this
     * @throws
     */
    public function  group(array $groupBy)
    {
        $this->groupBy = array_shift($groupBy);

        return $this;
    }

    /**
     * @param array $query
     */
    protected function prepareQuery(&$query)
    {
        foreach ($query as $fieldName => $fieldValue) {
            if (!isset($this->conditionDispatchers[array_key_first($fieldValue)])) {
                $query[$fieldName] = ['in' => $fieldValue];
            }
        }
    }

    /**
     * @param array $query
     * @param IItem|array $item
     * 
     * @return IHasCondition
     */
    protected function setItemConditions(array $query, IItem|array $item): IHasCondition
    {
        $itemCondition = new class() extends Item implements IHasCondition {
            use THasCondition;
            use THasValue;
        };
        foreach($query as $field => $condition) {
            if (is_array($condition) && isset($this->conditionDispatchers[array_key_first($condition)])) {
                $currentConditions = $itemCondition->getValue();
                $currentConditions[] = [
                    IHasValue::FIELD__VALUE => $item[$field],
                    IHasCondition::FIELD__CONDITION => array_key_first($condition)
                ];
                $itemCondition->setValue($currentConditions);
            }
        }

        return $itemCondition;
    }

    /**
     * Return DB file content as array
     * 
     * @return array
     */
    protected function getDbContent(): array
    {
        return json_decode(file_get_contents($this->dbPath), true);
    }

    /**
     * Rewrite DB file content
     * 
     * @return mixed false if it is failed, int otherwise
     */
    protected function setDbContent(array $db): mixed
    {
        return file_put_contents($this->dbPath, json_encode($db));
    }

    /**
     * Rewrite collection in a DB file content
     * 
     * @return mixed false if it is failed, int otherwise
     */
    protected function reloadDbFile(): mixed
    {
        $db = $this->getDbContent();
        $db[$this->tabelName] = $this->collection;

        return $this->setDbContent($db);
    }

    /**
     * @return void
     */
    protected function resetItem($item): void
    {
        if (isset($this->collection[$item[$this->getPk()]])) {
            $this->collection[$item[$this->getPk()]] = $item;
        }
    }

    /**
     * Remove item from the current collection state
     * 
     * @return void
     */
    protected function removeItem($item): void
    {
        if (isset($this->collection[$item[$this->getPk()]])) {
            unset($this->collection[$item[$this->getPk()]]);
        }
    }
}
