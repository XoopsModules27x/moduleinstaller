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
 * ModuleInstaller install hooks.
 */

use XoopsModules\Moduleinstaller\Helper;
use XoopsModules\Moduleinstaller\Utility;
use XoopsModules\Mtools\Common\Configurator;
use XoopsModules\Mtools\Module\Installer;

require \dirname(__DIR__) . '/bootstrap.php';

/**
 * Prepares system prior to attempting to install module.
 *
 * @return bool true if ready to install, false if not
 */
function xoops_module_pre_install_moduleinstaller(\XoopsModule $module): bool
{
    $mtoolsDependencyError = moduleinstaller_mtools_dependency_error();
    if ('' !== $mtoolsDependencyError) {
        $module->setErrors($mtoolsDependencyError);

        return false;
    }

    $utility = new Utility();

    if (!$utility::checkVerXoops($module) || !$utility::checkVerPhp($module)) {
        return false;
    }

    // Shared pre-install work (upload folders + drop tables). Module not registered yet,
    // so Configurator is built from the module directory on disk.
    Installer::prepare($module, new Configurator(\dirname(__DIR__)));

    return true;
}

/**
 * Performs tasks required during installation of the module.
 *
 * @return bool true if installation successful, false if not
 */
function xoops_module_install_moduleinstaller(\XoopsModule $module): bool
{
    $mtoolsDependencyError = moduleinstaller_mtools_dependency_error();
    if ('' !== $mtoolsDependencyError) {
        $module->setErrors($mtoolsDependencyError);

        return false;
    }

    $moduleDirName = \basename(\dirname(__DIR__));
    $helper        = Helper::getInstance();

    $helper->loadLanguage('admin');
    $helper->loadLanguage('modinfo');

    $moduleId = $module->getVar('mid');
    /** @var \XoopsGroupPermHandler $grouppermHandler */
    $grouppermHandler = \xoops_getHandler('groupperm');
    $grouppermHandler->addRight($moduleDirName . '_approve', 1, \XOOPS_GROUP_ADMIN, $moduleId);
    $grouppermHandler->addRight($moduleDirName . '_submit', 1, \XOOPS_GROUP_ADMIN, $moduleId);
    $grouppermHandler->addRight($moduleDirName . '_view', 1, \XOOPS_GROUP_ADMIN, $moduleId);
    $grouppermHandler->addRight($moduleDirName . '_view', 1, \XOOPS_GROUP_USERS, $moduleId);
    $grouppermHandler->addRight($moduleDirName . '_view', 1, \XOOPS_GROUP_ANONYMOUS, $moduleId);

    // Shared install filesystem work: upload folders + blank.png + testdata + .html purge.
    Installer::install($module, new Configurator(\dirname(__DIR__)));

    return true;
}
