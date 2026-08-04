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
 * Severity of one line in an install transcript.
 *
 * This is the thing core currently expresses as `style="color:#ff0000"`. Naming it
 * is what removes the inline style from the pipeline: the value travels as data and
 * the colour is decided once, in CSS, by the class the renderer picks.
 */
enum LogSeverity: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';

    /** CSS class the renderer emits. Empty for Info, which needs no marking. */
    public function cssClass(): string
    {
        return match ($this) {
            self::Info => '',
            self::Success => 'installer-log-success',
            self::Warning => 'installer-log-warning',
            self::Error => 'installer-log-error',
        };
    }
}
