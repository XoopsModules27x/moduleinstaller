<?php declare(strict_types=1);

namespace XoopsModules\Moduleinstaller\Report;

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
 * Verdict of one module operation.
 *
 * The string values are deliberately the same three the current
 * ModuleActionResult::STATUS_* constants use, so a stored or posted value from the
 * 2.8 module still maps onto this enum during the migration.
 */
enum Outcome: string
{
    case Ok = 'ok';
    case Skipped = 'skip';
    case Failed = 'fail';

    /** CSS class for the report row — the match that currently lives in AdminBulkPage. */
    public function cssClass(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::Skipped => 'warning',
            self::Failed => 'error',
        };
    }
}
