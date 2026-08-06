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
 * Turns core's legacy HTML install log into a list of {@see LogEvent}.
 *
 * This is the seam. `xoops_module_install()` returns a formatted HTML string and
 * will keep doing so for as long as XOOPS 2.7.x compatibility matters, so the string
 * cannot be legislated away — but it only has to be understood ONCE, here, at the
 * boundary. Everything past this class deals in data.
 *
 * The parsing steps are exactly the steps ModuleActionResult::messageHtml() already
 * performs. That is the point: today's renderer is already a parser that happens to
 * serialise straight back to HTML. Splitting it into parse (this class) plus render
 * ({@see LogEventHtmlRenderer}) is a refactor with the existing regression suite as
 * its equivalence gate — not a rewrite.
 *
 * When core 4.0 emits {@see ModuleOperationResult} directly, this class is deleted
 * and nothing else changes.
 */
final class LegacyModuleReportAdapter
{
    /** Markers, private to the parse. Input control bytes are stripped first. */
    private const M = "\x01";
    private const NBSP = "\u{00A0}";

    /** @return list<LogEvent> */
    public function parse(string $legacyHtml): array
    {
        if ('' === $legacyHtml) {
            return [];
        }

        return $this->toEvents($this->toMarkedText($legacyHtml));
    }

    /**
     * Build a full result from core's return value plus the verdict the caller
     * established by re-reading module state (which core's string cannot be trusted
     * for — see ModuleActionService::verifiedResult()).
     */
    public function result(string $dirname, string $action, Outcome $outcome, string $legacyHtml, ?string $reason = null): ModuleOperationResult
    {
        $events = $this->parse($legacyHtml);

        return match ($outcome) {
            Outcome::Ok => ModuleOperationResult::ok($dirname, $action, $events),
            Outcome::Skipped => ModuleOperationResult::skipped($dirname, $action, $reason ?? ''),
            Outcome::Failed => ModuleOperationResult::failed($dirname, $action, $reason ?? '', $events),
        };
    }

    /**
     * Steps 1–5 of the current messageHtml(), stopping one step short of escaping:
     * the result is PLAIN TEXT with emphasis/severity markers still embedded.
     */
    private function toMarkedText(string $text): string
    {
        $m = self::M;

        // 1. control bytes out — also makes the markers unforgeable
        $text = (string) \preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // 1b. normalise line endings. The step above deliberately keeps \r (it is
        //     not a control byte we want to silently delete), and lines are split on
        //     \n later, so CRLF input would otherwise leave a trailing \r inside a
        //     LogFragment — invisible once escaped, but wrong in a value object whose
        //     contract is clean text.
        $text = \str_replace(["\r\n", "\r"], "\n", $text);

        // 2. elements whose content is not text
        $text = (string) \preg_replace(
            '#<(script|style|template|noscript|textarea|title|xmp)\b[^>]*>.*?</\1\s*>#is',
            '',
            $text
        );

        // 3. the two constructs that carry meaning become markers
        $text = (string) \preg_replace(
            '#<span\s+style\s*=\s*(["\']?)\s*color\s*:\s*\#?(?:ff0000|f00|red)\s*;?\s*\1\s*>#i',
            $m . 'E',
            $text
        );
        // Every OTHER span gets an open marker too. It carries no severity, but it
        // does carry a </span>, and without a matching open that close would be
        // read as ending the red span enclosing it — silently stopping the error
        // run and repainting the rest of the transcript as ordinary output. Core
        // does nest spans, so this is the common case, not a hostile one. Runs
        // AFTER the red-span pass, which has already consumed those tags.
        $text = (string) \preg_replace('#<span(?:\s[^>]*)?>#i', $m . 'O', $text);
        $text = (string) \preg_replace('#</span\s*>#i', $m . 'e', $text);
        $text = (string) \preg_replace('#<(?:strong|b|em|i)(?:\s[^>]*)?>#i', $m . 'S', $text);
        $text = (string) \preg_replace('#</(?:strong|b|em|i)\s*>#i', $m . 's', $text);

        // 4. line structure
        $text = (string) \preg_replace('#<(?:br\s*/?|/p|/div|/li|/tr|/h[1-6])\s*>#i', "\n", $text);

        // 5. every other tag out, core's escaping collapsed. NO htmlspecialchars():
        //    this is text now, and text is what the value object should hold.
        return \html_entity_decode(\strip_tags($text), \ENT_QUOTES | \ENT_HTML5 | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Marked text to events.
     *
     * Emphasis and severity depths are intentionally declared OUTSIDE the line loop,
     * so an emphasis or error span that core opens before a <br> and closes after it
     * still applies to both lines. That reproduces what the string renderer did (one
     * <strong> spanning the break) while producing per-line balanced markup instead,
     * and core does emit spans that wrap a <br>. The cost is that an UNCLOSED span
     * colours every line after it; a stray close pops nothing rather than going
     * negative, so it cannot invert the state.
     *
     * Spans are tracked as a STACK, not a counter, because only the red ones carry
     * severity while all of them share one closing tag. A counter had to guess which
     * span a </span> belonged to, and guessed the innermost open one — so an
     * ordinary span nested inside a red one closed the red one, ending the error run
     * early. The stack records what each open span was, so each close ends its own.
     *
     * @return list<LogEvent>
     */
    private function toEvents(string $marked): array
    {
        $events = [];
        $emphasisDepth = 0;
        /** @var list<bool> $spanStack true for a red (error) span, false for an ordinary one */
        $spanStack = [];

        foreach (\explode("\n", $marked) as $line) {
            $fragments = [];
            $buffer = '';
            // Seeded from the state carried in, so a line that only CLOSES a span core
            // opened before the <br> still takes the severity. Reading the stack after
            // the loop instead would miss it: the closing marker has already popped
            // that span by then.
            $lineHadError = \in_array(true, $spanStack, true);

            $parts = \preg_split(
                '/(' . self::M . '[SsEeO])/',
                $line,
                -1,
                \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY
            );

            foreach ((array) $parts as $part) {
                switch ($part) {
                    case self::M . 'S':
                        $this->flushBuffer($buffer, $fragments, $emphasisDepth);
                        ++$emphasisDepth;

                        break;
                    case self::M . 's':
                        $this->flushBuffer($buffer, $fragments, $emphasisDepth);
                        $emphasisDepth = \max(0, $emphasisDepth - 1);

                        break;
                    case self::M . 'E':
                        $spanStack[] = true;
                        $lineHadError = true;

                        break;
                    case self::M . 'O':
                        // Ordinary span: no severity, but it owns the next close.
                        $spanStack[] = false;

                        break;
                    case self::M . 'e':
                        // Ends the innermost span, whatever kind it was. A close with
                        // nothing open pops nothing, which is how a stray </span> from
                        // core's unbalanced fragments stays harmless.
                        \array_pop($spanStack);

                        break;
                    default:
                        $buffer .= $part;
                }
            }
            $this->flushBuffer($buffer, $fragments, $emphasisDepth);

            $severity = ($lineHadError || \in_array(true, $spanStack, true))
                ? LogSeverity::Error
                : LogSeverity::Info;
            $event = $this->withIndentExtracted($severity, $fragments);

            // Blank lines are an artifact of core's unbalanced </div></a> fragments,
            // not information. Dropping them here is why the renderer needs no
            // "collapse runs of <br>" pass.
            if (! $event->isBlank()) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Move the pending text into a fragment, carrying the emphasis state it was
     * accumulated under, and reset the buffer.
     *
     * A method rather than the by-reference closure this used to be. The closure was
     * rebuilt on every line of every transcript, and — because static analysis reads a
     * closure body against the state at its DECLARATION, where the buffer has just
     * been set to '' — the `'' !== $buffer` guard was reported as always false. The
     * guard is real at runtime; it was the shape that could not be verified.
     *
     * @param list<LogFragment> $fragments
     */
    private function flushBuffer(string &$buffer, array &$fragments, int $emphasisDepth): void
    {
        if ('' === $buffer) {
            return;
        }

        $fragments[] = new LogFragment($buffer, $emphasisDepth > 0);
        $buffer = '';
    }

    /**
     * Core indents nested steps with '&nbsp;&nbsp;' per level. Pull that out of the
     * text into a depth, so the renderer can indent with CSS (or with anything else)
     * instead of the transcript carrying presentation inside its own text.
     *
     * Indent is measured in NBSP ONLY. Two rules follow from that, both deliberate:
     *
     *   - ASCII spaces and tabs are CONTENT, not structure. Core does not use them to
     *     nest, and a message that begins with one leading space means it, so leading
     *     whitespace stays in the text where the renderer will escape it.
     *   - an odd NBSP is not lost: only whole pairs become depth, and the remainder is
     *     put back at the head of the text.
     *
     * The match is on CHARACTERS, with /u. Doing this on bytes is a real bug and this
     * method had it: ltrim($text, " \t\u{00A0}") builds a byte set {0x20, 0x09, 0xC2,
     * 0xA0}, so a line starting with «, », ·, ¡, ¿, °, ± — anything in U+0080–U+00BF,
     * all of which begin with the byte 0xC2 — lost its lead byte, leaving an orphan
     * continuation byte that htmlspecialchars(ENT_SUBSTITUTE) turned into U+FFFD and
     * that made json_encode() of the plain text return false. Translated packs use
     * exactly those characters as bullets, so making the report translatable is what
     * would have exposed it.
     *
     * @param list<LogFragment> $fragments
     */
    private function withIndentExtracted(LogSeverity $severity, array $fragments): LogEvent
    {
        if ([] === $fragments) {
            return new LogEvent($severity, [], 0);
        }

        $first = $fragments[0];
        if (1 !== \preg_match('/^(?:\x{00A0})+/u', $first->text, $lead)) {
            return new LogEvent($severity, $fragments, 0);
        }

        $units = \mb_strlen($lead[0], 'UTF-8');
        $depth = \intdiv($units, 2);
        $remainder = \str_repeat(self::NBSP, $units % 2);
        $rest = $remainder . \substr($first->text, \strlen($lead[0]));

        $fragments[0] = new LogFragment($rest, $first->emphasised);
        if ('' === $rest) {
            \array_shift($fragments);
        }

        return new LogEvent($severity, \array_values($fragments), $depth);
    }
}
