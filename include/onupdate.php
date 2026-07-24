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
 * ModuleInstaller update hooks.
 */

use XoopsModules\Moduleinstaller\Helper;
use XoopsModules\Moduleinstaller\Utility;
use XoopsModules\Mtools\Common\Configurator;
use XoopsModules\Mtools\Module\Installer;

if ((!defined('XOOPS_ROOT_PATH')) || !($GLOBALS['xoopsUser'] instanceof \XoopsUser)
    || !$GLOBALS['xoopsUser']->isAdmin()) {
    exit('Restricted access' . PHP_EOL);
}

require \dirname(__DIR__) . '/bootstrap.php';

/**
 * Prepares system prior to attempting to update module.
 *
 * @return bool true if ready to update, false if not
 */
function xoops_module_pre_update_moduleinstaller(\XoopsModule $module): bool
{
    $mtoolsDependencyError = moduleinstaller_mtools_dependency_error();
    if ('' !== $mtoolsDependencyError) {
        $module->setErrors($mtoolsDependencyError);

        return false;
    }

    $utility = new Utility();

    $xoopsSuccess = $utility::checkVerXoops($module);
    $phpSuccess   = $utility::checkVerPhp($module);

    Installer::createUploadFolders(new Configurator(\dirname(__DIR__)));

    return $xoopsSuccess && $phpSuccess;
}

/**
 * Performs tasks required during update of the module.
 *
 * @param int|null $previousVersion
 * @return bool true if update successful, false if not
 */
function xoops_module_update_moduleinstaller(\XoopsModule $module, $previousVersion = null): bool
{
    $mtoolsDependencyError = moduleinstaller_mtools_dependency_error();
    if ('' !== $mtoolsDependencyError) {
        $module->setErrors($mtoolsDependencyError);

        return false;
    }

    $helper = Helper::getInstance();
    $helper->loadLanguage('common');
    $configurator = new Configurator(\dirname(__DIR__));

    // Shared update cleanup for legacy assets (safe to re-run).
    Installer::removeOldAssets($module, $configurator);
    Installer::createUploadFolders($configurator);
    Installer::copyBlankFiles($configurator);
    Installer::purgeHtmlTemplates($module);

    if (null !== $previousVersion && (int) $previousVersion < 240) {
        /** @var \XoopsGroupPermHandler $grouppermHandler */
        $grouppermHandler = \xoops_getHandler('groupperm');

        return (bool) $grouppermHandler->deleteByModule($module->getVar('mid'), 'item_read');
    }

    return true;
}
