<?php declare(strict_types=1);

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.
*/

/**
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author    Michael Beck (mamba)
 */


use Xmf\Module\Admin;
use XoopsModules\Moduleinstaller\Helper;

require \dirname(__DIR__) . '/bootstrap.php';

$moduleDirName      = \basename(\dirname(__DIR__));
$moduleDirNameUpper = \mb_strtoupper($moduleDirName);

$helper = Helper::getInstance();
$helper->loadLanguage('common');
$helper->loadLanguage('feedback');

$pathIcon32    = Admin::menuIconPath('');
$pathModIcon32 = XOOPS_URL . '/modules/' . $moduleDirName . '/assets/images/icons/32/';
if (is_object($helper->getModule()) && false !== $helper->getModule()->getInfo('modicons32')) {
    $pathModIcon32 = $helper->url($helper->getModule()->getInfo('modicons32'));
}

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_MENU_00,
    'link'  => 'admin/index.php',
    'icon'  => $pathIcon32 . '/home.png',
];

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_MENU_01,
    'link'  => 'admin/install.php',
    'icon'  => $pathIcon32 . '/add.png',
];

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_MENU_03,
    'link'  => 'admin/update.php',
    'icon'  => $pathIcon32 . '/update.png',
];

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_MENU_02,
    'link'  => 'admin/uninstall.php',
    'icon'  => $pathIcon32 . '/delete.png',
];

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_MENU_04,
    'link'  => 'admin/activate.php',
    'icon'  => $pathIcon32 . '/button_ok.png',
];

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_MENU_05,
    'link'  => 'admin/deactivate.php',
    'icon'  => $pathIcon32 . '/link_break.png',
];

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_MENU_06,
    'link'  => 'admin/sets.php',
    'icon'  => $pathIcon32 . '/exec.png',
];

$adminmenu[] = [
    'title' => _MI_MODULEINSTALLER_ADMIN_ABOUT,
    'link'  => 'admin/about.php',
    'icon'  => $pathIcon32 . '/about.png',
];

