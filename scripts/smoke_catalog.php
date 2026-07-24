<?php
require dirname(__DIR__, 3) . '/mainfile.php';
require_once XOOPS_ROOT_PATH . '/modules/moduleinstaller/class/ModuleCatalog.php';
$c = new XoopsModules\Moduleinstaller\ModuleCatalog();
echo 'install=' . count($c->candidatesFor('install')) . PHP_EOL;
echo 'needs=' . count($c->listNeedsUpdate()) . PHP_EOL;
echo 'deactivate=' . count($c->candidatesFor('deactivate')) . PHP_EOL;
