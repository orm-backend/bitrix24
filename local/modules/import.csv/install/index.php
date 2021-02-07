<?
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if (class_exists('import_csv')) {
    return;
}

class import_csv extends CModule {

    public $MODULE_ID;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $MODULE_GROUP_RIGHTS;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    
    private $errors;
    
    public function import_csv() {
        include __DIR__ . '/version.php';
        
        $this->MODULE_ID = 'import.csv';
        $this->MODULE_NAME = "Import CSV";
        $this->MODULE_DESCRIPTION = "Importing data from a CSV file";
        $this->PARTNER_NAME = 'Vitaliy Kovalenko';
        $this->MODULE_GROUP_RIGHTS = 'Y';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    public function doInstall() {
        global $APPLICATION, $USER, $step;

        if ($USER->IsAdmin()) {
            $step = (int) $step ? (int) $step : 1;
    
            if ($step === 2) {
                CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/{$this->MODULE_ID}/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);
                ModuleManager::registerModule($this->MODULE_ID);
                Loader::includeModule( $this->MODULE_ID );
                
                $GLOBALS["errors"] = $this->errors;
            }
            
            $APPLICATION->IncludeAdminFile('Install module ' . $this->MODULE_NAME, $_SERVER["DOCUMENT_ROOT"]."/local/modules/{$this->MODULE_ID}/install/install{$step}.php");
        }
    }
    
    public function doUninstall() {
        global $APPLICATION, $USER, $step;
        
        if ($USER->IsAdmin()) {
            $step = (int) $step ? (int) $step : 1;
            
            if ($step === 2) {
                DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/{$this->MODULE_ID}/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
                ModuleManager::unregisterModule($this->MODULE_ID);
                $GLOBALS["errors"] = $this->errors;
            }
            
            $APPLICATION->IncludeAdminFile('Uninstall module ' . $this->MODULE_NAME, $_SERVER["DOCUMENT_ROOT"]."/local/modules/{$this->MODULE_ID}/install/uninstall{$step}.php");
        }
    }

}