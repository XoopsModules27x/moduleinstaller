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
 * A run of PLAIN TEXT inside one transcript line, optionally emphasised.
 *
 * `$text` is text, not markup: no entities, no tags, no escaping applied yet.
 * Escaping is the renderer's job and happens exactly once, at the boundary — which
 * is the whole reason this type exists. `$emphasised` is what core expresses with
 * <strong> around a table name, a template filename or a module id.
 */
final readonly class LogFragment
{
    public function __construct(
        public string $text,
        public bool $emphasised = false,
    ) {
    }
}
