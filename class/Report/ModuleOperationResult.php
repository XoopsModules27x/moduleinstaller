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
 * The outcome of one module operation, as data.
 *
 * Compare with today's ModuleActionResult: same identity (dirname, action) and same
 * verdict, but the transcript is a list of {@see LogEvent} instead of a string of
 * HTML — so nothing downstream has to sanitise, and the same result can be rendered
 * to an admin page, a CLI run, a JSON API response or a test assertion without one
 * of those four having to un-escape the other three.
 *
 * Note what is NOT here: no messageHtml(), no htmlspecialchars, no class names. A
 * value object that knows how to render itself to HTML is the coupling that made
 * the sanitiser necessary in the first place.
 */
final readonly class ModuleOperationResult
{
    /** @param list<LogEvent> $events */
    private function __construct(
        public string $dirname,
        public string $action,
        public Outcome $outcome,
        public array $events,
        public ?string $reason = null,
    ) {
    }

    /** @param list<LogEvent> $events */
    public static function ok(string $dirname, string $action, array $events = []): self
    {
        return new self($dirname, $action, Outcome::Ok, $events);
    }

    /** A skip is a decision, not a failure: it always has a stated reason. */
    public static function skipped(string $dirname, string $action, string $reason): self
    {
        return new self($dirname, $action, Outcome::Skipped, [LogEvent::text($reason, LogSeverity::Warning)], $reason);
    }

    /**
     * A failure whose reason is NOT yet part of the transcript.
     *
     * The reason is prepended as an error event, so a consumer that only walks the
     * transcript still sees the verdict. Use this when reason and transcript come from
     * different places — a verdict this module established, plus whatever core said
     * while getting there.
     *
     * @param list<LogEvent> $events
     */
    public static function failed(string $dirname, string $action, string $reason, array $events = []): self
    {
        return new self(
            $dirname,
            $action,
            Outcome::Failed,
            [LogEvent::text($reason, LogSeverity::Error), ...$events],
            $reason
        );
    }

    /**
     * A failure whose transcript ALREADY states its reason.
     *
     * Needed because the legacy string does not separate verdict from transcript:
     * ModuleActionService composes a failure message as "<translated reason>: <core's
     * log>", so a projection of that string has only one body of text to work with.
     * Handing it to failed() prepended a copy of the whole transcript to itself, and
     * the plain-text view printed every line twice — visible in a CLI run, a log file
     * and any JSON consumer, though not in messageHtml(), which never looks at events.
     *
     * NOTE THE SIGNATURE: no $reason. It is DERIVED from the first event, not supplied
     * beside it. An earlier version took both and documented that the events "must
     * already contain $reason" — a docblock is not an invariant, and a constructor
     * that accepts two independent sources for one fact will eventually be handed two
     * different facts. Deriving it makes the divergence unrepresentable.
     *
     * The two constructors exist rather than a boolean flag because the caller always
     * knows which situation it is in, and the call site should say so.
     *
     * @param list<LogEvent> $events the transcript, whose first line states the reason
     */
    public static function failedWithTranscript(string $dirname, string $action, array $events): self
    {
        return new self(
            $dirname,
            $action,
            Outcome::Failed,
            $events,
            [] === $events ? '' : $events[0]->plainText()
        );
    }

    public function isOk(): bool
    {
        return Outcome::Ok === $this->outcome;
    }

    public function isSkipped(): bool
    {
        return Outcome::Skipped === $this->outcome;
    }

    public function isFailed(): bool
    {
        return Outcome::Failed === $this->outcome;
    }

    /**
     * True when any transcript line reported an error, even on an Ok outcome.
     *
     * DELIBERATELY INDEPENDENT OF $outcome, and it is worth saying why, because a
     * reviewer reasonably proposed collapsing the two: a failed result whose transcript
     * carries no red line answers false here, which reads like a contradiction.
     *
     * It is not one. The two answer different questions:
     *
     *   - $outcome is THIS MODULE'S verdict, established by re-reading module state
     *     after the action (see ModuleActionService::verifiedResult());
     *   - hasErrorEvents() is what CORE'S OWN LOG said while getting there.
     *
     * They disagree in both directions, and both disagreements are real and worth
     * seeing. Core routinely returns a log containing red lines for an operation that
     * nonetheless completed — a template that could not be added while the module
     * installed fine — and, more importantly, core's string is exactly the thing that
     * cannot be trusted for the verdict, which is why the verdict is re-derived at all.
     * Promoting the reason to an Error event to make the two agree would delete the
     * only signal that answers "core said this went fine; did its own log agree?".
     *
     * A renderer should therefore colour by {@see self::severity()}, which combines
     * them, and never by scanning events alone.
     */
    public function hasErrorEvents(): bool
    {
        foreach ($this->events as $event) {
            if (LogSeverity::Error === $event->severity) {
                return true;
            }
        }

        return false;
    }

    /**
     * The one severity that describes this result, for a consumer that wants one.
     *
     * The verdict wins where it is decisive — a failure is an error however calm its
     * transcript reads — and the transcript speaks where the verdict is not: an Ok
     * result whose log contains red lines is a Warning, not a clean Success, and that
     * is the case a transcript-only renderer would otherwise paint green.
     */
    public function severity(): LogSeverity
    {
        return match (true) {
            $this->isFailed() => LogSeverity::Error,
            $this->isSkipped() => LogSeverity::Warning,
            $this->hasErrorEvents() => LogSeverity::Warning,
            default => LogSeverity::Success,
        };
    }

    /** The whole transcript as plain text — CLI, log file, mail, test assertion. */
    public function plainText(): string
    {
        return \implode("\n", \array_map(static fn (LogEvent $e): string => $e->plainText(), $this->events));
    }

    /** @return array{dirname:string,action:string,outcome:string,severity:string,reason:?string,events:list<array{severity:string,depth:int,text:string}>} */
    public function toArray(): array
    {
        return [
            'dirname' => $this->dirname,
            'action' => $this->action,
            'outcome' => $this->outcome->value,
            // The combined verdict, so a JSON consumer does not have to re-implement
            // the outcome/transcript precedence rule to colour one row.
            'severity' => $this->severity()->value,
            'reason' => $this->reason,
            'events' => \array_map(
                static fn (LogEvent $e): array => [
                    'severity' => $e->severity->value,
                    'depth' => $e->depth,
                    'text' => $e->plainText(),
                ],
                $this->events
            ),
        ];
    }
}
