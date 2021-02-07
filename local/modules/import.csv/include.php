<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Loader;

if (!Loader::includeModule( 'crm' )) {
    throw new \Exception('CRM module is not installed.');
}
