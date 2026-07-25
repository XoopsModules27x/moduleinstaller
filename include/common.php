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

/**
 * ModuleInstaller common include.
 */

use Xmf\Module\Admin;
use XoopsModules\Moduleinstaller\Helper;
use XoopsModules\Moduleinstaller\Utility;
use XoopsModules\Mtools\Module\ModuleContext;

/** @var Admin $adminObject */
/** @var Utility $utility */
/** @var Helper $helper */
require \dirname(__DIR__) . '/bootstrap.php';

$moduleDirName = \basename(\dirname(__DIR__));
$moduleDirNameUpper = \mb_strtoupper($moduleDirName);

/** @var \XoopsDatabase $db */
$db = \XoopsDatabaseFactory::getDatabaseConnection();
$helper = Helper::getInstance();
$utility = new Utility();

$helper->loadLanguage('common');

// Standard {UP}_* path/URL constants from the shared mtools module context.
$context = ModuleContext::fromHelper($helper);
$context->defineConstants();

if (! \defined($moduleDirNameUpper . '_AUTHOR_LOGOIMG')) {
    \define($moduleDirNameUpper . '_AUTHOR_LOGOIMG', \constant($moduleDirNameUpper . '_URL') . '/assets/images/logoModule.png');
}
if (! \defined($moduleDirNameUpper . '_IMAGE_URL')) {
    \define($moduleDirNameUpper . '_IMAGE_URL', \constant($moduleDirNameUpper . '_IMAGES_URL'));
}
if (! \defined($moduleDirNameUpper . '_IMAGE_PATH')) {
    \define($moduleDirNameUpper . '_IMAGE_PATH', \constant($moduleDirNameUpper . '_IMAGES_PATH'));
}
if (! \defined($moduleDirNameUpper . '_CONSTANTS_DEFINED')) {
    \define($moduleDirNameUpper . '_CONSTANTS_DEFINED', 1);
}

$pathIcon16 = Admin::iconUrl('', '16');
$pathIcon32 = Admin::iconUrl('', '32');

$icons = [
    'edit' => "<img src='" . $pathIcon16 . "/edit.png'  alt=" . _EDIT . "' align='middle'>",
    'delete' => "<img src='" . $pathIcon16 . "/delete.png' alt='" . _DELETE . "' align='middle'>",
    'clone' => "<img src='" . $pathIcon16 . "/editcopy.png' alt='" . _CLONE . "' align='middle'>",
    'preview' => "<img src='" . $pathIcon16 . "/view.png' alt='" . _PREVIEW . "' align='middle'>",
    'print' => "<img src='" . $pathIcon16 . "/printer.png' alt='" . _CLONE . "' align='middle'>",
    'pdf' => "<img src='" . $pathIcon16 . "/pdf.png' alt='" . _CLONE . "' align='middle'>",
    'add' => "<img src='" . $pathIcon16 . "/add.png' alt='" . _ADD . "' align='middle'>",
    '0' => "<img src='" . $pathIcon16 . "/0.png' alt='" . 0 . "' align='middle'>",
    '1' => "<img src='" . $pathIcon16 . "/1.png' alt='" . 1 . "' align='middle'>",
];

$debug = false;

$myts = \MyTextSanitizer::getInstance();

if (! isset($GLOBALS['xoopsTpl']) || ! ($GLOBALS['xoopsTpl'] instanceof \XoopsTpl)) {
    require_once $GLOBALS['xoops']->path('class/template.php');
    $GLOBALS['xoopsTpl'] = new \XoopsTpl();
}

$GLOBALS['xoopsTpl']->assign('mod_url', $helper->url());
if (\is_object($helper->getModule())) {
    $pathModIcon16 = $helper->getModule()->getInfo('modicons16');
    $pathModIcon32 = $helper->getModule()->getInfo('modicons32');

    $GLOBALS['xoopsTpl']->assign('pathModIcon16', \XOOPS_URL . '/modules/' . $moduleDirName . '/' . $pathModIcon16);
    $GLOBALS['xoopsTpl']->assign('pathModIcon32', $pathModIcon32);
}

\xoops_loadLanguage('main', $moduleDirName);
if (\class_exists('D3LanguageManager')) {
    require_once \XOOPS_TRUST_PATH . '/libs/altsys/class/D3LanguageManager.class.php';
    $langman = D3LanguageManager::getInstance();
    $langman->read('main.php', $moduleDirName);
}
