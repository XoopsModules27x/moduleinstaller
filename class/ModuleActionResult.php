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

use XoopsModules\Moduleinstaller\Report\LegacyModuleReportAdapter;
use XoopsModules\Moduleinstaller\Report\LogEventHtmlRenderer;

/**
 * Result of a single module action (install/uninstall/activate/deactivate/update).
 *
 * The message is core's install log: intentionally formatted HTML that also
 * interpolates third-party strings without escaping them (modulesadmin.php
 * interpolates $module->getVar('name'), $tpl['file'], $block['name'] and
 * $db->error() raw), so it is untrusted markup that still carries meaning.
 *
 * Rendering policy: the log is treated as a TRANSCRIPT, not a document — it is
 * parsed into {@see Report\LogEvent} values and rendered from string constants, so
 * no value from the message can become a tag, an attribute name or an attribute
 * value. See {@see Report\LegacyModuleReportAdapter} for the parse and
 * {@see Report\LogEventHtmlRenderer} for the render; the properties that follow
 * hold by construction rather than by policy:
 *
 *   - the output can contain no attribute except the fixed classes the renderer
 *     writes, so there is no URL policy, no scheme denylist, no attribute allowlist;
 *   - escaping happens exactly once, in the renderer, so entities core already
 *     escaped round-trip instead of showing up as literal "&amp;nbsp;";
 *   - no optional extension is involved, so there is no fallback branch that can
 *     fail open, and no walk over a mutable tree that can fail to terminate.
 *
 * The module logo is resolved from validated module metadata by
 * {@see AdminBulkPage::moduleLogoUrl()}, never from this message, so dropping the
 * log's own <img> loses nothing.
 *
 * LEGACY COMPATIBILITY TYPE. Being replaced by {@see Report\ModuleOperationResult},
 * which carries the transcript as data instead of as a string of HTML. The migration
 * path exists in code, not only in prose: {@see ModuleActionService::operationResult()}
 * and operationResults() return the new type, and {@see self::toOperationResult()}
 * converts a result you already hold — so no new code has to name this class.
 *
 * No removal version is named, and that is deliberate. This shape is what every
 * third-party caller of ModuleActionService uses, so it is supported for the whole
 * XOOPS 2.8 line; removal becomes a question at XOOPS 4.0, where core emits the
 * structured type itself and this class and the adapter disappear together. New code
 * should take the Report types.
 *
 * Intentionally NOT tagged `@deprecated` yet. runOne()/runMany() — the primary public
 * API of ModuleActionService — still return this type, and AdminBulkPage still renders
 * from it, so the formal tag would mark as deprecated a type whose replacement no
 * caller in this module has adopted. That is not a documentation nicety: it makes
 * every internal use a deprecation violation (116 of them, which is what turned the
 * QA gate red), training readers to ignore the tag on the release where it finally
 * means something. The tag goes on when the internal consumers have moved.
 */
final readonly class ModuleActionResult
{
    public const STATUS_OK = 'ok';
    public const STATUS_SKIP = 'skip';
    public const STATUS_FAIL = 'fail';

    /**
     * @param string $dirname Module dirname
     * @param string $status  One of STATUS_*
     * @param string $message Human-readable message (untrusted HTML from core)
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
     * Parse then render, as two steps that can be used separately. Both objects are
     * stateless and a report renders a handful of rows, so they are built per call:
     * `static $x = new Foo()` in a method needs PHP 8.3, and a readonly class cannot
     * hold a `static ?Foo $x = null` cache at all, and this module supports PHP 8.2.
     */
    public function messageHtml(): string
    {
        return (new LogEventHtmlRenderer())->render(
            (new LegacyModuleReportAdapter())->parse($this->message)
        );
    }

    /**
     * The transcript as structured data — the shape everything should move to.
     *
     * @return list<Report\LogEvent>
     */
    public function events(): array
    {
        return (new LegacyModuleReportAdapter())->parse($this->message);
    }

    /** The transcript as plain text, for a CLI run, a log file or a mail. */
    public function messageText(): string
    {
        return \implode(
            "\n",
            \array_map(static fn (Report\LogEvent $e): string => $e->plainText(), $this->events())
        );
    }

    /**
     * This result, projected onto the shape that replaces it.
     *
     * The projection lives HERE, on the legacy type, and not in the Report package
     * — deliberately. A strangler fig only works if the dependency points from the old
     * code to the new: Report\* must never mention ModuleActionResult, or deleting this
     * class would mean editing the package that outlives it. When core emits events
     * directly, this method is deleted along with its class and nothing in Report has
     * to change.
     *
     * It is a pure function of $this, which is also what makes it testable without a
     * running XOOPS. That matters: the duplicated-transcript bug this method now fixes
     * survived a green suite precisely because the composition had only ever existed
     * inside ModuleActionService, where no unit test could reach it.
     *
     * Two properties of core's string decide the mapping:
     *
     *   - a SKIP's message is already plain prose ('Already installed'), never core
     *     HTML, because nothing ran. It becomes the reason and nothing else;
     *   - a FAILURE's message is '<translated reason>: <core's log>' — verdict and
     *     transcript concatenated, with no separator this class can rely on. So the
     *     first line becomes the reason and the transcript is kept AS IS.
     *     ModuleOperationResult::failed() would prepend the reason as a fresh event,
     *     which for this input means prepending a copy of the transcript to itself;
     *     failedWithTranscript() is the constructor for text that already states its
     *     own reason.
     *
     * That coupling is a property of core's string, not of this design, and it is
     * exactly what disappears when core stops concatenating.
     */
    public function toOperationResult(): Report\ModuleOperationResult
    {
        $events = $this->events();

        if ($this->isOk()) {
            return Report\ModuleOperationResult::ok($this->dirname, $this->action, $events);
        }

        if ($this->isSkip()) {
            // A skip's message is prose, not a log, so the whole of it is the reason —
            // and skipped() builds its own single event from it.
            return Report\ModuleOperationResult::skipped(
                $this->dirname,
                $this->action,
                [] === $events ? \trim($this->message) : $events[0]->plainText()
            );
        }

        // No reason argument: failedWithTranscript() derives it from the first event,
        // which is the one place the verdict can be. Passing it separately would be
        // handing the same fact in twice and inviting the two copies to drift.
        return Report\ModuleOperationResult::failedWithTranscript($this->dirname, $this->action, $events);
    }
}
