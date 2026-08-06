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
 * One line of an install transcript: "table x8a9_quotes_quote created".
 *
 * This is the unit core actually produces — it appends one of these to $msgs per
 * step — and then throws away by imploding them into HTML. Modelling it keeps the
 * three facts that survive the round trip today (severity, nesting depth, which
 * words were emphasised) as data instead of as markup to be re-parsed.
 */
final readonly class LogEvent
{
    /** @param list<LogFragment> $fragments */
    public function __construct(
        public LogSeverity $severity,
        public array $fragments,
        public int $depth = 0,
    ) {
    }

    /** Convenience for the common "one unemphasised line" case. */
    public static function text(string $text, LogSeverity $severity = LogSeverity::Info, int $depth = 0): self
    {
        return new self($severity, [new LogFragment($text)], $depth);
    }

    /** The line as plain text — for logs, CLI output, JSON, tests. */
    public function plainText(): string
    {
        return \implode('', \array_map(static fn (LogFragment $f): string => $f->text, $this->fragments));
    }

    public function isBlank(): bool
    {
        return '' === \trim($this->plainText());
    }
}
