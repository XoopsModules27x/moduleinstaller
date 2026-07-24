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
use XoopsModules\Moduleinstaller\{
    Helper,
    Utility
};

/** @var Admin $adminObject */
/** @var Helper $helper */
/** @var Utility $utility */

require \dirname(__DIR__, 3) . '/include/cp_header.php';
require_once \XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

require \dirname(__DIR__) . '/bootstrap.php';

$mtoolsDependencyError = moduleinstaller_mtools_dependency_error();
if ('' !== $mtoolsDependencyError) {
    redirect_header(\XOOPS_URL . '/admin.php', 3, $mtoolsDependencyError);
    exit;
}

require \dirname(__DIR__) . '/include/common.php';

$helper        = Helper::getInstance();
$moduleDirName = \basename(\dirname(__DIR__));
$utility       = new Utility();
$adminObject   = Admin::getInstance();

$pathIcon16    = Admin::iconUrl('', '16');
$pathIcon32    = Admin::iconUrl('', '32');
$pathModIcon32 = $helper->getModule()->getInfo('modicons32');

$myts = \MyTextSanitizer::getInstance();
if (!isset($GLOBALS['xoopsTpl']) || !($GLOBALS['xoopsTpl'] instanceof \XoopsTpl)) {
    require_once $GLOBALS['xoops']->path('class/template.php');
    $xoopsTpl = new \XoopsTpl();
}

// Load language files
$helper->loadLanguage('admin');
$helper->loadLanguage('modinfo');
$helper->loadLanguage('common');
$helper->loadLanguage('main');
