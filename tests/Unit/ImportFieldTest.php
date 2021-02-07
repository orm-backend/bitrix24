<?php
namespace Tests\Unit;

use Faker\Factory;
use Import\CSV\ImportField;
use Tests\BitrixTestCase;

/**
 * ImportField test case.
 */
class ImportFieldTest extends BitrixTestCase
{
    private $faker;
    
    public function __construct()
    {
        parent::__construct();
        $this->faker = Factory::create();
    }
    
    /**
     * Tests ImportField::getMultiFieldNames()
     */
    public function testGetMultiFieldNames()
    {
        $names = ImportField::getMultiFieldNames();
        $this->assertNotNull($names);
        $this->assertIsArray($names);
        $this->assertCount(31, $names);
    }

    /**
     * Tests ImportField::getLeadFieldNames()
     */
    public function testGetLeadFieldNames()
    {
        $names = ImportField::getLeadFieldNames();
        $this->assertNotNull($names);
        $this->assertIsArray($names);
        $this->assertCount(66, $names);
    }

    /**
     * Tests ImportField::getDealFieldNames()
     */
    public function testGetDealFieldNames()
    {
        $names = ImportField::getDealFieldNames();
        $this->assertNotNull($names);
        $this->assertIsArray($names);
        $this->assertCount(36, $names);
    }

    /**
     * Tests ImportField::buildFieldMultiData()
     */
    public function testBuildFieldMultiData()
    {
        $data = [
            'EMAIL_WORK' => $this->faker->email,
            'EMAIL_HOME' => $this->faker->email,
            'PHONE_WORK' => $this->faker->phoneNumber,
        ];

        $result = ImportField::buildFieldMultiData($data);
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('EMAIL', $result);
        $this->assertArrayHasKey('PHONE', $result);
        $this->assertCount(2, $result['EMAIL']);
        $this->assertCount(1, $result['PHONE']);
    }
    
    /**
     * Tests ImportField::buildFieldMultiData()
     */
    public function testBuildFieldMultiIncorrectData1()
    {
        $this->expectException(\Exception::class);
        $data = [
            'EMAIL_QWERTY' => $this->faker->email,
        ];
        
        ImportField::buildFieldMultiData($data);
    }
    
    /**
     * Tests ImportField::buildFieldMultiData()
     */
    public function testBuildFieldMultiIncorrectData2()
    {
        $this->expectException(\Exception::class);
        $data = [
            'QWERTY_HOME' => $this->faker->phoneNumber,
        ];
        
        ImportField::buildFieldMultiData($data);
    }
}

