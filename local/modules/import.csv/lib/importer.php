<?php
namespace Import\CSV;

use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

class Importer
{

    const FILE_INPUT = 'csv-file';
    
    const INN_KEY = 'INN';

    /**
     * 
     * @var array
     */
    private $dealMapping;

    /**
     *
     * @var array
     */
    private $leadMapping;

    public function __construct()
    {
        $this->dealMapping = json_decode(Option::get('import.csv', 'csv_to_deal_mapping', '{}'), true);
        $this->leadMapping = json_decode(Option::get('import.csv', 'csv_to_lead_mapping', '{}'), true);
    }

    /**
     * Entry poin.
     *
     * @throws \RuntimeException
     * @return array
     */
    public static function processRequest(): array
    {
        global $USER, $APPLICATION;

        if (! $USER->IsAuthorized()) {
            throw new \RuntimeException('Authorization required.');
        }

        $modulePermission = $APPLICATION->GetGroupRight('import.csv');

        if ($modulePermission < 'W') {
            throw new \RuntimeException('Access denied.');
        }

        $request = Context::getCurrent()->getRequest();

        if (! $request->isPost()) {
            throw new \RuntimeException('Invalid request method.');
        }

        $tmp = $request->getFile(self::FILE_INPUT);

        if ($tmp['error']) {
            throw new \RuntimeException('Failed to upload file.');
        }

        $instance = new static();
        return $instance->import($tmp['tmp_name']);
    }
    
    /**
     * Imports data from the specified CSV file.
     * 
     * @param string $filePath The full path to CSV file.
     * @param bool $transactionally Execute database queries in transactions for each row or not
     * (false for testing).
     * @return array
     */
    public function import(string $filePath, bool $transactionally = true): array
    {
        $csv = $this->parseCsv($filePath);
        $total = count($csv);
        $created = 0;
        $errors = [];
        $connection = Context::getCurrent()->getApplication()->getConnection();
        
        foreach ($csv as $data) {
            if ($transactionally) {
                $connection->startTransaction();
            }
            
            try {
                if (array_key_exists(self::INN_KEY, $data) && $data[self::INN_KEY]) {
                    $normalized = $this->normalizeDealData($data);
                    $this->createDeal($normalized, $data[self::INN_KEY]);
                } else {
                    $normalized = $this->normalizeLeadData($data);
                    $this->createLead($normalized);
                }
                
                if ($transactionally) {
                    $connection->commitTransaction();
                }
                
                $created ++;
            } catch (\Exception $e) {
                if ($transactionally) {
                    $connection->rollbackTransaction();
                }
                
                $errors[] = $e->getMessage();
            }
        }
        
        return [
            $total,
            $created,
            $errors
        ];
    }

    /**
     * @return array
     */
    public function getDealMapping()
    {
        return $this->dealMapping;
    }

    /**
     * @return array
     */
    public function getLeadMapping()
    {
        return $this->leadMapping;
    }

    /**
     * Parses CSV data into key-value pairs.
     *
     * @param string $filePath
     * @throws ImporterException
     * @return array[]
     */
    private function parseCsv(string $filePath): array
    {
        $header = null;
        $data = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            try {
                while (($row = fgetcsv($handle, null, ';')) !== false) {
                    if (! $header) {
                        $header = $row;
                    } else {
                        $result = array_combine($header, $row);

                        if ($result === false) {
                            throw new ImporterException('The number of elements for each row isn\'t equal.');
                        }

                        $data[] = $result;
                    }
                }
            } finally {
                fclose($handle);
            }
        }

        return $data;
    }

    /**
     * Converts CSV columns to the lead field names.
     *
     * @param array $data
     * @return array
     */
    private function normalizeDealData(array $data): array
    {
        $result = [];

        foreach ($this->dealMapping as $dirtyKey => $fieldName) {
            if (array_key_exists($dirtyKey, $data)) {
                $result[$fieldName] = $data[$dirtyKey];
            }
        }

        return $result;
    }

    /**
     * Converts CSV columns to the deal field names.
     *
     * @param array $data
     * @return array
     */
    private function normalizeLeadData(array $data): array
    {
        $result = [];

        foreach ($this->leadMapping as $dirtyKey => $fieldName) {
            if (array_key_exists($dirtyKey, $data)) {
                $result[$fieldName] = $data[$dirtyKey];
            }
        }

        return $result;
    }

    /**
     * Creates a new deal.
     * If the user is specified, then the deal is assigned to him, otherwise it is assigned to the company responsible man.
     *
     * @param array $data
     * @param string $inn
     * @throws ImporterException When user or company not found.
     */
    private function createDeal(array $data, string $inn)
    {
        $company = DAO::fetchCompanyByINN($inn);

        if (! $company) {
            throw new ImporterException('Could not find company by INN ' . $inn);
        }

        $data['COMPANY_ID'] = $company->getId();
        $assignedById = isset($data['ASSIGNED_BY_ID']) && intval($data['ASSIGNED_BY_ID']) > 0 ? (int) $data['ASSIGNED_BY_ID'] : $company->getAssignedById();

        if (! DAO::checkUserExists($assignedById)) {
            throw new ImporterException('Could not find user by ID ' . $assignedById);
        }

        $data['ASSIGNED_BY_ID'] = $assignedById;
        $data['COMMENTS'] = 'INN: ' . $inn;
        DAO::createDeal($data);
    }

    /**
     * Creates a new lead.
     * If the user is specified, then the lead is assigned to him, otherwise it is assigned to the current user.
     * 
     * @param array $data
     * @throws ImporterException When no user is found by the given id.
     */
    private function createLead(array $data)
    {
        global $USER;

        $assignedById = isset($data['ASSIGNED_BY_ID']) && intval($data['ASSIGNED_BY_ID']) > 0 ? (int) $data['ASSIGNED_BY_ID'] : $USER->GetID();

        if (! DAO::checkUserExists($assignedById)) {
            throw new ImporterException('Could not find user by ID ' . $assignedById);
        }

        $data['ASSIGNED_BY_ID'] = $assignedById;
        DAO::createLead($data);
    }
}
