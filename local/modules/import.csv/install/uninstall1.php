<?
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc; 

$context = Application::getInstance()->getContext();
?>
<form action="<?=$context->getRequest()->getRequestUri()?>">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=$context->getLanguage()?>">
	<input type="hidden" name="id" value="import.csv">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<?echo CAdminMessage::ShowMessage(Loc::getMessage("MOD_UNINST_WARN"))?>
	<p><?=Loc::getMessage("MOD_UNINST_SAVE")?></p>
	<p><input type="checkbox" name="save_tables" id="save_tables" value="Y" checked><label for="save_tables"><?=Loc::getMessage("MOD_UNINST_SAVE_TABLES")?></label></p>
	<input type="submit" name="inst" value="<?=Loc::getMessage("MOD_UNINST_DEL")?>">
</form>