<?php
namespace Tests\Intergation;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\DB\Connection;
use Import\CSV\Importer;
use Tests\BitrixTestCase;
use Import\CSV\ImporterException;

/**
 * Importer test case.
 */
class ImporterTest extends BitrixTestCase
{
    const MODULE_ID = 'import.csv';
    
    const TEST_USER_ID = 1;
    
    /**
     *
     * @var Importer
     */
    private $importer;
    
    /**
     *
     * @var Connection
     */
    private $connection;

    public function __construct()
    {
        global $USER;
        
        parent::__construct();
        
        if (! Loader::includeModule(self::MODULE_ID)) {
            throw new \Exception('Import CSV module is not installed.');
        }
        
        $USER->Authorize(self::TEST_USER_ID); // authorize
        $this->importer = new Importer();
        $this->connection = Context::getCurrent()->getApplication()->getConnection();
    }
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() : void
    {
        parent::setUp();
        $this->connection->startTransaction();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() : void
    {
        $this->connection->rollbackTransaction();
        parent::tearDown();
    }
    
    /**
     * Tests Importer->__construct()
     */
    public function test__construct()
    {
        $dealMapping = $this->importer->getDealMapping();
        $leadMapping = $this->importer->getLeadMapping();
        $this->assertNotNull($dealMapping, 'Deal mapping is null');
        $this->assertNotNull($leadMapping, 'Lead mapping is null');
        $this->assertIsArray($dealMapping, 'Deal mapping is not an array');
        $this->assertIsArray($leadMapping, 'Lead mapping is not an array');
        $this->assertTrue(array_keys($dealMapping) > 0, 'A deal mapping should be defined.');
        $this->assertTrue(array_keys($leadMapping) > 0, 'A lead mapping should be defined.');
        $this->assertTrue(count(array_keys($dealMapping)) === count(array_values($dealMapping)), 'The number of deal keys and its values are different.');
        $this->assertTrue(count(array_keys($leadMapping)) === count(array_values($leadMapping)), 'The number of lead keys and its values are different.');
    }

    /**
     * Tests Importer->import()
     */
    public function testImportIncorrectCsv()
    {
        $this->expectExceptionObject(new ImporterException('The number of elements for each row isn\'t equal.'));
        $csvPath = realpath(dirname(__FILE__).'/../resources/incorrect_format.csv');
        $this->importer->import($csvPath, false);
    }
    
    /**
     * Tests Importer->import()
     */
    public function testImportUnknownInn()
    {
        $csvPath = realpath(dirname(__FILE__).'/../resources/unknown_inn.csv');
        $result = $this->importer->import($csvPath, false);
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertTrue(count($result) === 3);
        
        $total = $result[0];
        $created = $result[1];
        $errors = $result[2];
        
        $this->assertTrue($total === 3);
        $this->assertTrue($created === 0);
        $this->assertIsArray($errors);
        $this->assertTrue(count($errors) === 3);
    }
    
    /**
     * Tests Importer->import()
     */
    public function testImportUnknownUser()
    {
        $csvPath = realpath(dirname(__FILE__).'/../resources/unknown_user.csv');
        $result = $this->importer->import($csvPath, false);
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertTrue(count($result) === 3);
        
        $total = $result[0];
        $created = $result[1];
        $errors = $result[2];
        
        $this->assertTrue($total === 1);
        $this->assertTrue($created === 0);
        $this->assertIsArray($errors);
        $this->assertTrue(count($errors) === 1);
        $this->assertEquals('Could not find user by ID 253', $errors[0]);
    }
    
    /**
     * Tests Importer->import()
     */
    public function testImportLead()
    {
        $csvPath = realpath(dirname(__FILE__).'/../resources/lead.csv');
        $result = $this->importer->import($csvPath, false);
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertTrue(count($result) === 3);
        
        $total = $result[0];
        $created = $result[1];
        $errors = $result[2];
        
        $this->assertTrue($total === 1);
        $this->assertIsArray($errors);
        $this->assertEmpty($errors, ($errors[0] ?: 'Unknown error'));
        $this->assertTrue($created === 1);
    }
    
    /**
     * Tests Importer->import()
     */
    public function testImportDeal()
    {
        $csvPath = realpath(dirname(__FILE__).'/../resources/deal.csv');
        $result = $this->importer->import($csvPath, false);
        $this->assertNotNull($result, 'The result is null');
        $this->assertIsArray($result, 'The result is not an array');
        $this->assertTrue(count($result) === 3, 'Incorrect result array length');
        
        $total = $result[0];
        $created = $result[1];
        $errors = $result[2];
        
        $this->assertTrue($total === 1);
        $this->assertIsArray($errors);
        $this->assertEmpty($errors, ($errors[0] ?: 'Unknown error'));
        $this->assertTrue($created === 1);
    }
}

