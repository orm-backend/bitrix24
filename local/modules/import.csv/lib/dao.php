<?php
namespace Import\CSV;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;

class DAO
{

    /**
     *
     * @param string $inn
     * @return NULL|\Bitrix\Crm\EO_Company
     */
    public static function fetchCompanyByINN(string $inn)
    {
        if (! $inn) { // empty string
            return null;
        }

        $requisite = RequisiteTable::getList([
            'filter' => [
                '='.EntityRequisite::INN => $inn,
                '=ENTITY_TYPE_ID' => \CCrmOwnerType::Company
            ],
            'limit' => 1 // records are assumed to be unique
        ])->fetchObject();

        if (! $requisite || ! $requisite->getEntityId()) {
            return null;
        }

        return CompanyTable::getById($requisite->getEntityId())->fetchObject();
    }

    /**
     * 
     * @param int $id
     * @return bool
     */
    public static function checkUserExists(int $id) : bool
    {
        return (bool) UserTable::getCount([ 'ID' => $id ]);
    }
    
    /**
     * 
     * @param int $companyId
     * @param int $userId
     * @throws ImporterException
     */
    public static function setCompanyAssignedBy(int $companyId, int $userId) : void
    {
        $entity = new \CCrmCompany();
        $data = ['ASSIGNED_BY_ID' =>  $userId];
        $entity->Update($companyId, $data);
        
        if ($entity->LAST_ERROR) {
            throw new ImporterException($entity->LAST_ERROR);
        }
    }

    /**
     * Creates a new Lead. This method <b>must be done transactionally</b>.
     * 
     * @param array $data
     * @param bool $checkPermissions
     * @throws ImporterException
     * @return int The newly created Lead ID
     */
    public static function createLead(array $data, bool $checkPermissions = true): int
    {
        /**
         * TODO: Typecasting should be performed for all Bitrix data types.
         * This logic should be moved to a separate class.
         * Why doesn't Bitrix do it and we have to do it manually?
         */
        
        $map = LeadTable::getMap();
        
        foreach ($data as $key => $value) {
            if (!empty($map[$key]['data_type']) && $map[$key]['data_type'] == 'datetime') {
                if (!$value) {
                    $data[$key] = null;
                    continue;
                }
                
                $timestamp = strtotime($value);
                
                if ($timestamp === false) {
                    throw new ImporterException("Field {$key} contains incorrect datetime value {$value}.");
                }
                
                $data[$key] = date('d.m.Y', $timestamp);
            }
        }
        
        /*** <-- Typecasting ***/
        
        $multiFieldNames = ImportField::getMultiFieldNames();
        $leadData = [];
        $fmData = [];
        
        foreach ($data as $name => $value) {
            if (array_search($name, $multiFieldNames) !== false) {
                $fmData[$name] = $value;
            } else {
                $leadData[$name] = $value;
            }
        }
        
        if ($fmData) {
            $leadData['FM'] = ImportField::buildFieldMultiData($fmData);
        }

        $entity = new \CCrmLead($checkPermissions);
        $elementId = $entity->Add($leadData);
        
        if ($entity->LAST_ERROR) {
            throw new ImporterException($entity->LAST_ERROR);
        }

        return $elementId;
    }

    /**
     * Creates a new Deal. This method <b>must be done transactionally</b>.
     * 
     * @param array $data
     * @param bool $checkPermissions
     * @throws ImporterException
     * @return int
     */
    public static function createDeal(array $data, bool $checkPermissions = true): int
    {
        /**
         * TODO: Typecasting should be performed for all Bitrix data types.
         * This logic should be moved to a separate class.
         * Why doesn't Bitrix do it and we have to do it manually?
         */
        
        $map = DealTable::getMap();
        
        foreach ($data as $key => $value) {
            if (!empty($map[$key]['data_type']) && $map[$key]['data_type'] == 'datetime') {
                if (!$value) {
                    $data[$key] = null;
                    continue;
                }
                
                $timestamp = strtotime($value);
                
                if ($timestamp === false) {
                    throw new ImporterException("Field {$key} contains incorrect datetime value {$value}.");
                }
                
                // DateTime::createFromTimestamp($timestamp)->format(\CSite::GetDateFormat('SHORT')); -> FriFri.OctOct.2020202020202020 ;)
                $data[$key] = date('d.m.Y', $timestamp); // Y-m-d Doesn't pass Bitrix validation
            }
        }
        
        /*** <-- Typecasting ***/
        
        $entity = new \CCrmDeal($checkPermissions);
        $elementId = $entity->Add($data);
        
        if ($entity->LAST_ERROR) {
            throw new ImporterException($entity->LAST_ERROR);
        }
        
        return $elementId;
    }
    
}
