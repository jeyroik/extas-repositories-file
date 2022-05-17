<?php
namespace tests;

use \PHPUnit\Framework\TestCase;
use \extas\components\repositories\clients\databases\DbCurrent;
use \extas\interfaces\repositories\clients\IClientTable;

/**
 * Class TraitsTest
 *
 * @author jeyroik <jeyroik@gmail.com>
 */
class JsonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $env = Dotenv::create(getcwd() . '/tests/');
        $env->load();
    }

    public function testGetJSONTable()
    {
        $db = new class extends DbCurrent {
            public static function reset()
            {
                static::$tables = [];
            }

            public static function getTables()
            {
                return static::$tables;
            }
        };

        putenv('TEST_TABLE__NOT_EXISTING_REPO_NAME=table_1');
        putenv('TEST_DB__NOT_EXISTING_REPO_NAME=extas_tests');
        putenv('TEST_DSN__NOT_EXISTING_REPO_NAME=resources/.json');
        putenv('TEST_DRIVER__NOT_EXISTING_REPO_NAME=file');
        $db::reset();
        $table = $db::getTable('not.existing.repo.name', 'test');
        $this->assertInstanceOf(IClientTable::class, $table);
        $must = [
            'testnot.existing.repo.name' => $table
        ];
        $this->assertEquals($must, $db::getTables());
    }
}
