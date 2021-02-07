<?
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc; 

$context = Application::getInstance()->getContext();
?>
<form action="<?=$context->getRequest()->getRequestUri()?>" name="form1">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=$context->getLanguage()?>">
	<input type="hidden" name="id" value="import.csv">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="2">
<table cellpadding="3" cellspacing="0" border="0" width="0%">
		<tr>
			<td></td>
			<td></td>
		</tr>
	</table>
<br>
	<input type="submit" name="inst" value="<?=Loc::getMessage("MOD_INSTALL")?>">
</form>
