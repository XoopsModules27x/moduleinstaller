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

/** @return object */
$moduleDirName      = \basename(\dirname(__DIR__));
$moduleDirNameUpper = \mb_strtoupper($moduleDirName);

return [
    'name'          => \mb_strtoupper($moduleDirName) . ' PathConfigurator',
    'dirname'       => $moduleDirName,
    'admin'         => XOOPS_ROOT_PATH . '/modules/' . $moduleDirName . '/admin',
    'modPath'       => XOOPS_ROOT_PATH . '/modules/' . $moduleDirName,
    'modUrl'        => XOOPS_URL . '/modules/' . $moduleDirName,
    'uploadPath'    => XOOPS_UPLOAD_PATH . '/' . $moduleDirName,
    'uploadUrl'     => XOOPS_UPLOAD_URL . '/' . $moduleDirName,
    'uploadFolders' => [
        XOOPS_UPLOAD_PATH . '/' . $moduleDirName,
        XOOPS_UPLOAD_PATH . '/' . $moduleDirName . '/category',
        XOOPS_UPLOAD_PATH . '/' . $moduleDirName . '/screenshots',
        //XOOPS_UPLOAD_PATH . '/flags'
    ],
];
