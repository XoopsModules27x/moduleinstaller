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
final readonly class ModuleActionResult
{
    public const STATUS_OK = 'ok';
    public const STATUS_SKIP = 'skip';
    public const STATUS_FAIL = 'fail';

    /**
     * @param string $dirname Module dirname
     * @param string $status  One of STATUS_*
     * @param string $message Human-readable message (may contain HTML from core)
     * @param string $action  Action attempted (install, activate, …)
     */
    public function __construct(
        public string $dirname,
        public string $status,
        public string $message,
        public string $action = '',
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

    /**
     * Message rendered safe for admin output.
     *
     * Core modulesadmin returns HTML install logs, but a module's own (possibly
     * third-party) name/version is reflected inside them, so the message is fully
     * untrusted. A tag allowlist is not enough — strip_tags() keeps attributes on
     * permitted tags, so a name like "<p onmouseover=…>" would still execute. Instead:
     * turn block/line breaks into newlines, strip EVERY tag, HTML-escape the remaining
     * text, then re-introduce only <br> for the preserved breaks. The sole markup in
     * the output is that generated <br>, so no injected attribute can survive.
     */
    public function messageHtml(): string
    {
        $text = \preg_replace('#<(?:br\s*/?|/p|/div|/li|/tr)>#i', "\n", $this->message) ?? $this->message;
        $text = \strip_tags($text);
        $escaped = \htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        return \nl2br($escaped, false);
    }
}
