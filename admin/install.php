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


use XoopsModules\Moduleinstaller\AdminBulkPage;
use XoopsModules\Moduleinstaller\ModuleActionService;

require_once __DIR__ . '/admin_header.php';
xoops_cp_header();
AdminBulkPage::serve(ModuleActionService::ACTION_INSTALL);
require_once __DIR__ . '/admin_footer.php';
