<?
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid()) return;

$context = Application::getInstance()->getContext();

if($ex = $APPLICATION->GetException())
	echo CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
	    "MESSAGE" => Loc::getMessage("MOD_UNINST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
else
    echo CAdminMessage::ShowNote(Loc::getMessage("MOD_UNINST_OK"));
?>
<form action="<?=$context->getRequest()->getRequestUri()?>">
	<input type="hidden" name="lang" value="<?=$context->getLanguage()?>">
	<input type="submit" name="" value="<?=Loc::getMessage("MOD_BACK")?>">
</form>
