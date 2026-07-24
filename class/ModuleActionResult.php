<?php declare(strict_types=1);

namespace XoopsModules\Moduleinstaller;

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
 * Result of a single module action (install/activate/etc.).
 */
final class ModuleActionResult
{
    public const STATUS_OK   = 'ok';
    public const STATUS_SKIP = 'skip';
    public const STATUS_FAIL = 'fail';

    /**
     * @param string $dirname Module dirname
     * @param string $status  One of STATUS_*
     * @param string $message Human-readable message (may contain HTML from core)
     * @param string $action  Action attempted (install, activate, …)
     */
    public function __construct(
        public readonly string $dirname,
        public readonly string $status,
        public readonly string $message,
        public readonly string $action = '',
    ) {
    }

    public function isOk(): bool
    {
        return self::STATUS_OK === $this->status;
    }

    public function isSkip(): bool
    {
        return self::STATUS_SKIP === $this->status;
    }

    public function isFail(): bool
    {
        return self::STATUS_FAIL === $this->status;
    }
}
