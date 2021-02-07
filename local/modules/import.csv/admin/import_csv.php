<?
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Import\CSV\Importer;

define('ADMIN_MODULE_NAME', 'import.csv');

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');

if (!Loader::includeModule('import.csv')) {
    throw new \Exception('The Import CSV module not installed.');
}

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();
$total = 0;
$created = 0;
$errors = [];

if ($request->isPost() && check_bitrix_sessid()) {
    try {
        [$total, $created, $errors] = Importer::processRequest();
    } catch (\Exception $e) {
        $errors[] = $e->getMessage();
    }
}

$modulePermission = $APPLICATION->GetGroupRight( 'import.csv' );

if ($modulePermission < 'W') {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

$APPLICATION->SetTitle('Import comma-separated data');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$tabControl = new CAdminTabControl('tabControl', array(
    array(
        'DIV' => 'upload-csv',
        'TAB' => 'Upload & import',
        'TITLE' => 'Uploading a CSV file and importing data from it',
    ),
));
?>
<?if ($errors) :?>
<div class='adm-info-message-wrap adm-info-message-red'>
	<div class='adm-info-message'>
		<div class='adm-info-message-title'>Errors: <?=count($errors);?></div>
		<?foreach ($errors as $error) :?>
		<span><?=$error?></span><br>
		<?endforeach;?>
		<div class='adm-info-message-icon'></div>
	</div>
</div>
<?endif;?>
<?if ($created) :?>
<div class='adm-info-message-wrap adm-info-message-green'>
	<div class='adm-info-message'>
		<div class='adm-info-message-title'>Created: <?=$created?></div>
		<span>Total: <?=$total?></span>
		<div class='adm-info-message-icon'></div>
	</div>
</div>
<?endif;?>
<?
$tabControl->begin();
?>
<form name='upload-csv' method='post' action='<?=sprintf('%s?lang=%s', $request->getRequestedPage(), LANGUAGE_ID)?>' enctype="multipart/form-data">
<?=bitrix_sessid_post();?>
<?$tabControl->beginNextTab();?>
<tr>
	<td>
		<input type="file" name="<?=Importer::FILE_INPUT;?>" accept="text/csv">
	</td>
</tr>
<?$tabControl->buttons( ['btnApply' => false] );?>
<?$tabControl->end();?>
</form>
<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>