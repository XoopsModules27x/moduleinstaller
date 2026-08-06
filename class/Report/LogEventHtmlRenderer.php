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
 * Renders {@see LogEvent} list to admin HTML.
 *
 * The only place in the feature that produces markup, and the only place that calls
 * htmlspecialchars(). Every tag and class below is a literal in this file: there is
 * no path by which a value from the transcript becomes a tag name, an attribute name
 * or an attribute value. That is the same invariant the current ModuleActionResult
 * holds — it has simply moved to where it belongs, and the value object no longer
 * knows that HTML exists.
 *
 * In 4.0 this class is the natural thing to replace with a Smarty template, at which
 * point the module has no PHP that emits markup at all.
 */
final class LogEventHtmlRenderer
{
    private const NBSP = "\u{00A0}";

    /** @param list<LogEvent> $events */
    public function render(array $events): string
    {
        $lines = [];
        foreach ($events as $event) {
            $lines[] = $this->renderEvent($event);
        }

        return \implode("<br>\n", $lines);
    }

    private function renderEvent(LogEvent $event): string
    {
        $inner = '';
        foreach ($event->fragments as $fragment) {
            $text = $this->escape($fragment->text);
            $inner .= $fragment->emphasised ? '<strong>' . $text . '</strong>' : $text;
        }

        $class = $event->severity->cssClass();
        if ('' !== $class) {
            $inner = '<span class="' . $class . '">' . $inner . '</span>';
        }

        // Indent stays outside the severity span, exactly where core put it, and is
        // emitted as NBSP so it survives HTML whitespace collapsing. A depth-based
        // CSS indent (padding-inline-start) is now possible instead — the value
        // object carries the depth, so that is a renderer decision, not a data one.
        return $this->escape(\str_repeat(self::NBSP . self::NBSP, \max(0, $event->depth))) . $inner;
    }

    private function escape(string $text): string
    {
        return \htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
