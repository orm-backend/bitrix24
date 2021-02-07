<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Loader;

if (!Loader::includeModule('import.csv')) return;

if ($APPLICATION->GetGroupRight('import.csv') != 'D') {
    $aMenu = [
        [
            'parent_menu' => 'global_menu_content',
            'sort' => 400,
            'text' => 'Import CSV',
            'items_id' => 'import_csv',
            "icon" => "iblock_menu_icon_types",
            'items' => [
                [
                    'text' => 'Upload',
                    'url' => 'import_csv.php',
                    'title' => 'Got to CSV upload form',
                    'more_url' => []
                ],
            ],
        ],
    ];
    
    return $aMenu;
}

return false;
