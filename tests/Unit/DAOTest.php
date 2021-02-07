<?php
namespace Tests\Unit;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\DB\Connection;
use Faker\Factory;
use Faker\Generator;
use Import\CSV\DAO;
use Tests\BitrixTestCase;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Type\DateTime;

/**
 * DAO test case.
 */
class DAOTest extends BitrixTestCase
{

    const MODULE_ID = 'import.csv';

    const INN = '7711413223';

    const TEST_USER_ID = 1;

    /**
     * 
     * @var Generator
     */
    private $faker;

    /**
     * 
     * @var Connection
     */
    private $connection;

    public function __construct()
    {
        parent::__construct();

        if (! Loader::includeModule(self::MODULE_ID)) {
            throw new \Exception('Import CSV module is not installed.');
        }

        $this->faker = Factory::create();
        $this->connection = Context::getCurrent()->getApplication()->getConnection();
    }

    /**
     *
     * {@inheritdoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->startTransaction();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        $this->connection->rollbackTransaction();
        parent::tearDown();
    }

    /**
     * Tests DAO::fetchCompanyByINN()
     */
    public function testFetchCompanyByINN()
    {
        $this->insertCompanyTestRequisiteIfNotExists();
        $company = DAO::fetchCompanyByINN(self::INN);
        $this->assertNotNull($company);
    }

    /**
     * Tests DAO::fetchCompanyByINN()
     */
    public function testFetchCompanyByEmptyINN()
    {
        $this->insertCompanyTestRequisiteIfNotExists();
        $company = DAO::fetchCompanyByINN('');
        $this->assertNull($company);
    }
    
    /**
     * Tests DAO::fetchCompanyByINN()
     */
    public function testFetchCompanyByIncorrectINN()
    {
        $this->insertCompanyTestRequisiteIfNotExists();
        $company = DAO::fetchCompanyByINN(PHP_INT_MAX);
        $this->assertNull($company);
    }

    /**
     * Tests DAO::checkUserExists()
     */
    public function testCheckUserExists()
    {
        $this->insertCompanyTestRequisiteIfNotExists();
        $exists = DAO::checkUserExists(self::TEST_USER_ID);
        $this->assertTrue($exists);
    }

    /**
     * Tests DAO::checkUserExists()
     */
    public function testCheckUserNotExists()
    {
        $this->insertCompanyTestRequisiteIfNotExists();
        $exists = DAO::checkUserExists(PHP_INT_MAX);
        $this->assertFalse($exists);
    }

    /**
     * Tests DAO::setCompanyAssignedBy()
     */
    public function testSetCompanyAssignedBy()
    {
        $this->insertCompanyTestRequisiteIfNotExists();
        $company = DAO::fetchCompanyByINN(self::INN);
        DAO::setCompanyAssignedBy($company->getId(), self::TEST_USER_ID);
        $company = CompanyTable::getById($company->getId())->fetchObject();
        $this->assertEquals(self::TEST_USER_ID, $company->getAssignedById());
    }

    /**
     * Tests DAO::createLead()
     */
    public function testCreateLead()
    {
        $data = [
            'ASSIGNED_BY_ID' => self::TEST_USER_ID,
            'NAME' => $this->faker->firstName,
            'LAST_NAME' => $this->faker->lastName,
            'POST' => $this->faker->sentence,
            'COMMENTS' => $this->faker->sentence,
            'EMAIL_WORK' => $this->faker->email,
            'PHONE_WORK' => $this->faker->phoneNumber,
        ];
        
        $id = DAO::createLead($data, false);
        $this->assertNotNull($id);
        $this->assertGreaterThan(0, $id);

        $lead = LeadTable::getList([
            'select' => array_keys($data),
            'filter' => ['ID' => $id],
            'limit' => 1
        ])->fetch();

        $this->assertNotNull($lead);
        $this->assertIsArray($lead);
        $this->assertEquals($data['ASSIGNED_BY_ID'], $lead['ASSIGNED_BY_ID']);
        $this->assertEquals($data['NAME'], $lead['NAME']);
        $this->assertEquals($data['LAST_NAME'], $lead['LAST_NAME']);
        $this->assertEquals($data['POST'], $lead['POST']);
        $this->assertEquals($data['COMMENTS'], $lead['COMMENTS']);
        $this->assertEquals($data['EMAIL_WORK'], $lead['EMAIL_WORK']);
        $this->assertEquals($data['PHONE_WORK'], $lead['PHONE_WORK']);
    }

    /**
     * Tests DAO::createDeal()
     */
    public function testCreateDeal()
    {
        $this->insertCompanyTestRequisiteIfNotExists();
        $company = DAO::fetchCompanyByINN(self::INN);
        $data = [
            'COMPANY_ID' => $company->getId(),
            'ASSIGNED_BY_ID' => $company->getAssignedById() ?: self::TEST_USER_ID,
            'TITLE' => $this->faker->sentence,
            'COMMENTS' => $this->faker->sentence,
            'BEGINDATE' => $this->faker->date('Y-m-d')
        ];

        $id = DAO::createDeal($data, false);
        
        $this->assertNotNull($id);
        $this->assertGreaterThan(0, $id);
        
        $deal = DealTable::getList([
            'select' => array_keys($data),
            'filter' => ['ID' => $id],
            'limit' => 1
        ])->fetch();
        
        $this->assertNotNull($deal);
        $this->assertIsArray($deal);
        $this->assertEquals($data['COMPANY_ID'], $deal['COMPANY_ID']);
        $this->assertEquals($data['ASSIGNED_BY_ID'], $deal['ASSIGNED_BY_ID']);
        $this->assertEquals($data['TITLE'], $deal['TITLE']);
        $this->assertEquals($data['COMMENTS'], $deal['COMMENTS']);
        $this->assertIsObject($deal['BEGINDATE']);
        $this->assertInstanceOf(DateTime::class, $deal['BEGINDATE']);
        /**
         * 
         * @var \Bitrix\Main\Type\DateTime $date
         */
        $date = $deal['BEGINDATE'];
        $this->assertEquals($data['BEGINDATE'], $date->format('Y-m-d'));
    }

    private function insertCompanyTestRequisiteIfNotExists()
    {
        $requisite = RequisiteTable::getList([
            'filter' => [
                EntityRequisite::INN => self::INN
            ],
            'limit' => 1
        ])->fetch();

        if (! $requisite) {
            $company = CompanyTable::getList([
                'select' => [
                    'ID'
                ],
                'limit' => 1
            ])->fetchObject();

            if (! $company) {
                throw new \Exception('No company found.');
            }

            $requisite = RequisiteTable::getList([
                'select' => [
                    'ID'
                ],
                'filter' => [
                    'ENTITY_ID' => $company['ID'],
                    \CCrmOwnerType::Company
                ],
                'limit' => 1
            ])->fetch();

            if ($requisite) {
                RequisiteTable::update($requisite['ID'], [
                    EntityRequisite::INN => self::INN
                ]);
            } else {
                $result = RequisiteTable::add([
                    'ENTITY_ID' => $company['ID'],
                    'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                    'NAME' => 'Test requisite',
                    EntityRequisite::INN => self::INN
                ]);

                $this->requisiteId = $result->getId();
            }
        }
    }

}
