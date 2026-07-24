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
 * mtools dependency shim for ModuleInstaller.
 *
 * Thin, mtools-absence-safe guard. Version-check and message logic live in
 * {@see \XoopsModules\Mtools\Module\ConsumerRuntime}.
 *
 */

use XoopsModules\Mtools\Module\ConsumerRuntime;

if (!\function_exists('moduleinstaller_mtools_dependency_error')) {
    /**
     * @return string Empty when mtools is ready; human-readable error otherwise
     */
    function moduleinstaller_mtools_dependency_error(): string
    {
        if (!\class_exists(ConsumerRuntime::class)) {
            return 'The mtools module files are missing. Install mtools before installing or running ModuleInstaller.';
        }

        // API 1.0.0 + module 1.2.0 (Configurator::forModule, Module\Installer, ModuleContext)
        return ConsumerRuntime::dependencyError('1.0.0', '1.2.0');
    }
}
