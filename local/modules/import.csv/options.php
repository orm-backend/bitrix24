<?
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Import\CSV\ImportField;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'import.csv');

if (!$USER->isAdmin()) {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

Loader::includeModule( ADMIN_MODULE_NAME );
$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

$tabControl = new CAdminTabControl("tabControl", array(
    array(
        "DIV" => 'edit1',
        "TAB" => 'Deal',
        "TITLE" => 'CSV to Deal field mapping',
    ),
    array(
        "DIV" => 'edit2',
        "TAB" => 'Lead',
        "TITLE" => 'CSV to Lead field mapping',
    ),
    array(
        "DIV" => 'edit3',
        "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS"),
    ),
));

if ($request->isPost() && check_bitrix_sessid()) {
    if (!empty($restore)) {
        Option::delete(ADMIN_MODULE_NAME);
        
        CAdminMessage::showMessage([
            "MESSAGE" => 'Settings restored',
            "TYPE" => "OK",
        ]);
    } else if (!empty($save)) {
        $dealKeys = $request->getPost('deal-key');
        $dealFields = $request->getPost('deal-field');
        $leadKeys = $request->getPost('lead-key');
        $leadFields = $request->getPost('lead-field');
        $dealOptionsPosted = [];
        $leadOptionsPosted = [];
        
        for ($i = 0; $i < count($dealKeys); $i++) {
            $dealKey = filter_var( trim($dealKeys[$i]), FILTER_SANITIZE_STRING );
            $dealField = isset($dealFields[$i]) ? filter_var( trim($dealFields[$i], FILTER_SANITIZE_STRING )) : null;
            
            if ($dealKey && $dealField) {
                $dealOptionsPosted[$dealKey] = $dealField;
            }
        }
        
        for ($i = 0; $i < count($leadKeys); $i++) {
            $leadKey = filter_var( trim($leadKeys[$i]), FILTER_SANITIZE_STRING );
            $leadField = isset($leadFields[$i]) ? filter_var( trim($leadFields[$i], FILTER_SANITIZE_STRING )) : null;
            
            if ($leadKey && $leadField) {
                $leadOptionsPosted[$leadKey] = $leadField;
            }
        }
        
        Option::set(
            ADMIN_MODULE_NAME,
            "csv_to_deal_mapping",
            json_encode($dealOptionsPosted)
        );
        
        Option::set(
            ADMIN_MODULE_NAME,
            "csv_to_lead_mapping",
            json_encode($leadOptionsPosted)
        );
        
        CAdminMessage::showMessage([
            "MESSAGE" => 'Settings stored',
            "TYPE" => "OK",
        ]);
    } else {
        CAdminMessage::showMessage('Incorrect request');
    }
}

$dealOptions = json_decode( Option::get(ADMIN_MODULE_NAME, 'csv_to_deal_mapping', '{}'), true );
$leadOptions = json_decode( Option::get(ADMIN_MODULE_NAME, 'csv_to_lead_mapping', '{}'), true );
$dealFieldNames = ImportField::getDealFieldNames();
$leadFieldNames = ImportField::getLeadFieldNames();

$tabControl->Begin();
?>
<form method="post" action="<?=sprintf('%s?lang=%s&mid=%s', $request->getRequestedPage(), LANGUAGE_ID, ADMIN_MODULE_NAME)?>">
<?=bitrix_sessid_post();?>
<?$tabControl->BeginNextTab();?>
<?foreach ($dealOptions as $key => $field) :?>
<tr>
    <td width="50%">
    	<input type="text" name="deal-key[]" value="<?=$key?>">
    </td>
    <td width="50%">
        <select name="deal-field[]">
        	<option value=""></option>
        	<?foreach ($dealFieldNames as $option) :?>
        	<option value="<?=$option?>" <?=($option == $field ? 'selected' : '')?>><?=$option?></option>
        	<?endforeach;?>
        </select>
    </td>
</tr>
<?endforeach;?>
<tr>
    <td width="50%">
    	<input type="text" name="deal-key[]">
    </td>
    <td width="50%">
        <select name="deal-field[]">
        	<option value=""></option>
        	<?foreach ($dealFieldNames as $option) :?>
        	<option value="<?=$option?>"><?=$option?></option>
        	<?endforeach;?>
        </select>
    </td>
</tr>
<?$tabControl->BeginNextTab();?>
<?foreach ($leadOptions as $key => $field) :?>
<tr>
    <td width="50%">
    	<input type="text" name="lead-key[]" value="<?=$key?>">
    </td>
    <td width="50%">
        <select name="lead-field[]">
        	<option value=""></option>
        	<?foreach ($leadFieldNames as $option) :?>
        	<option value="<?=$option?>" <?=($option == $field ? 'selected' : '')?>><?=$option?></option>
        	<?endforeach;?>
        </select>
    </td>
</tr>
<?endforeach;?>
<tr>
    <td width="50%">
    	<input type="text" name="lead-key[]">
    </td>
    <td width="50%">
        <select name="lead-field[]">
        	<option value=""></option>
        	<?foreach ($leadFieldNames as $option) :?>
        	<option value="<?=$option?>"><?=$option?></option>
        	<?endforeach;?>
        </select>
    </td>
</tr>
<?$tabControl->BeginNextTab();?>
<?$Update = $save; $module_id = ADMIN_MODULE_NAME;?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->buttons();?>
<input type="submit"
       name="save"
       value="<?=Loc::getMessage("MAIN_SAVE") ?>"
       title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE") ?>"
       class="adm-btn-save"
       />
<input type="submit"
       name="restore"
       title="<?=Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
       onclick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
       value="<?=Loc::getMessage("MAIN_RESTORE_DEFAULTS") ?>"
       />
<?$tabControl->End();?>
</form>
