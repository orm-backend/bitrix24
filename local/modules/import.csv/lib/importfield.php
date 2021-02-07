<?php
namespace Import\CSV;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;

class ImportField
{

    /**
     * Gets FM field map.
     * @return array
     */
    static public function getMultiFieldNames(): array
    {
        $names = [];

        foreach (\CCrmFieldMulti::GetEntityTypes() as $typeId => $type) {
            foreach (array_keys($type) as $valueType) {
                $names[] = $typeId . '_' . $valueType;
            }
        }

        return $names;
    }

    /**
     * Gets the lead entity field names.
     * @return array
     */
    static public function getLeadFieldNames(): array
    {
        $names = [];

        foreach (LeadTable::getMap() as $key => $definition) {
            if (is_numeric($key) || $key == 'ID') {
                continue;
            }

            if (isset($definition['reference']) || isset($definition['expression'])) {
                continue;
            }

            $names[] = $key;
        }

        $names = array_merge($names, self::getMultiFieldNames());

        return $names;
    }

    /**
     * Gets the deal entity field names.
     * @return array
     */
    static public function getDealFieldNames(): array
    {
        $names = [];

        foreach (DealTable::getMap() as $key => $definition) {
            if (is_numeric($key) || $key == 'ID') {
                continue;
            }

            if (isset($definition['reference']) || isset($definition['expression'])) {
                continue;
            }

            $names[] = $key;
        }

        return $names;
    }

    /**
     * Prepares data for saving to the <code>FieldMulti</code> entity.
     * 
     * @param array $data Such as <code>['EMAIL_WORK' => 'root@example.com']</code>
     * @throws ImporterException Thrown when the FM type or FM value type is invalid.
     * @return array
     */
    public static function buildFieldMultiData(array $data): array
    {
        $emails = [];
        $phones = [];
        $webs = [];
        $ims = [];

        foreach ($data as $name => $value) {
            $pair = explode('_', $name);

            if (count($pair) !== 2) {
                throw new \Exception('Unknown FM name.');
            }
            
            switch ($pair[0]) {
                case \CCrmFieldMulti::EMAIL:
                    self::validateFMValueType(\CCrmFieldMulti::EMAIL, $pair[1]);
                    $key = 'n' . (count($emails) + 1);
                    $emails[$key] = [
                        'VALUE' => $value,
                        'VALUE_TYPE' => $pair[1],
                    ];
                    
                    break;
                case \CCrmFieldMulti::PHONE:
                    self::validateFMValueType(\CCrmFieldMulti::PHONE, $pair[1]);
                    $key = 'n' . (count($phones) + 1);
                    $phones[$key] = [
                        'VALUE' => $value,
                        'VALUE_TYPE' => $pair[1],
                    ];
                    
                    break;
                case \CCrmFieldMulti::WEB:
                    self::validateFMValueType(\CCrmFieldMulti::WEB, $pair[1]);
                    $key = 'n' . (count($webs) + 1);
                    $webs[$key] = [
                        'VALUE' => $value,
                        'VALUE_TYPE' => $pair[1],
                    ];
                    
                    break;
                case \CCrmFieldMulti::IM:
                    self::validateFMValueType(\CCrmFieldMulti::IM, $pair[1]);
                    $key = 'n' . (count($ims) + 1);
                    $ims[$key] = [
                        'VALUE' => $value,
                        'VALUE_TYPE' => $pair[1],
                    ];
                    
                    break;
                default:
                    throw new ImporterException('Unknown FM type.');
            }
        }
        
        $result = [];
        
        if ($emails) {
            $result[\CCrmFieldMulti::EMAIL] = $emails;
        }
        
        if ($phones) {
            $result[\CCrmFieldMulti::PHONE] = $phones;
        }
        
        if ($webs) {
            $result[\CCrmFieldMulti::WEB] = $webs;
        }
        
        if ($ims) {
            $result[\CCrmFieldMulti::IM] = $ims;
        }
        
        return $result;
    }
    
    private static function validateFMValueType(string $typeId, string $valueType): void
    {
        $types = \CCrmFieldMulti::GetEntityTypes();
        $type = $types[$typeId];
        
        if (!array_key_exists($valueType, $type)) {
            throw new ImporterException('Unknown FM value type.');
        }
    }
}
