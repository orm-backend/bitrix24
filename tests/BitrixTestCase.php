<?php
namespace Tests;

use PHPUnit\Framework\TestCase as TestBase;

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/**
 *
 * @author vitaliy
 *        
 */
class BitrixTestCase extends TestBase
{
    public function __construct()
    {
        parent::__construct();
    }
}
