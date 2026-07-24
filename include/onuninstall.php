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
 * ModuleInstaller uninstall hooks.
 */

use XoopsModules\Moduleinstaller\Helper;
use XoopsModules\Mtools\Common\FilesManagement;

require \dirname(__DIR__) . '/bootstrap.php';

/**
 * Prepares system prior to attempting to uninstall module.
 *
 * @return bool true if ready to uninstall, false if not
 */
function xoops_module_pre_uninstall_moduleinstaller(\XoopsModule $module): bool
{
    return true;
}

/**
 * Performs tasks required during uninstallation of the module.
 *
 * @return bool true if uninstallation successful, false if not
 */
function xoops_module_uninstall_moduleinstaller(\XoopsModule $module): bool
{
    $moduleDirName = \basename(\dirname(__DIR__));
    $helper        = Helper::getInstance();
    $helper->loadLanguage('admin');
    $helper->loadLanguage('common');

    $success = true;
    $oldDirs = [$GLOBALS['xoops']->path("uploads/{$moduleDirName}")];
    foreach ($oldDirs as $oldDir) {
        $dirInfo = new \SplFileInfo($oldDir);
        if ($dirInfo->isDir()) {
            if (!FilesManagement::rrmdir($oldDir)) {
                if (\defined('_CO_MODULEINSTALLER_ERROR_BAD_DEL_PATH')) {
                    $module->setErrors(\sprintf(\_CO_MODULEINSTALLER_ERROR_BAD_DEL_PATH, $oldDir));
                } else {
                    $module->setErrors('Could not delete path: ' . $oldDir);
                }
                $success = false;
            }
        }
        unset($dirInfo);
    }

    return $success;
}
