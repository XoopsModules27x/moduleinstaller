<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\Report\LegacyModuleReportAdapter;
use XoopsModules\Moduleinstaller\Report\LogEvent;
use XoopsModules\Moduleinstaller\Report\LogEventHtmlRenderer;
use XoopsModules\Moduleinstaller\Report\LogSeverity;
use XoopsModules\Moduleinstaller\Report\Outcome;

/**
 * Fixture corpus for the legacy-transcript parser.
 *
 * This class is the module's trust boundary: everything upstream of it is core's
 * unescaped HTML, everything downstream is data. Its contract is a round trip, so
 * it is reviewed by re-running these cases rather than by re-reading the regexes.
 */
#[CoversClass(LegacyModuleReportAdapter::class)]
final class LegacyModuleReportAdapterTest extends TestCase
{
    private const NBSP = "\u{00A0}";

    #[Test]
    public function emptyInputProducesNoEvents(): void
    {
        self::assertSame([], (new LegacyModuleReportAdapter())->parse(''));
    }

    #[Test]
    public function plainTextBecomesOneInfoEvent(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('Module installed');

        self::assertCount(1, $events);
        self::assertSame('Module installed', $events[0]->plainText());
        self::assertSame(LogSeverity::Info, $events[0]->severity);
        self::assertSame(0, $events[0]->depth);
    }

    #[Test]
    public function lineBreakingTagsSplitEvents(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('first<br>second</div>third');

        self::assertSame(['first', 'second', 'third'], $this->texts($events));
    }

    #[Test]
    public function emphasisTagsBecomeEmphasisedFragments(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('table <strong>x_quotes</strong> created');

        self::assertCount(1, $events);
        self::assertSame('table x_quotes created', $events[0]->plainText());

        $emphasised = \array_values(\array_filter(
            $events[0]->fragments,
            static fn (\XoopsModules\Moduleinstaller\Report\LogFragment $f): bool => $f->emphasised
        ));
        self::assertCount(1, $emphasised);
        self::assertSame('x_quotes', $emphasised[0]->text);
    }

    #[Test]
    public function redSpanMarksTheLineAsError(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('<span style="color:#ff0000">could not add</span>');

        self::assertCount(1, $events);
        self::assertSame(LogSeverity::Error, $events[0]->severity);
        self::assertSame('could not add', $events[0]->plainText());
    }

    /**
     * Core emits spans that wrap a <br>. Both halves are the same red run, so both
     * lines are errors — this is why the depth counters live outside the line loop.
     */
    #[Test]
    public function errorSpanWrappingALineBreakColoursBothLines(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('<span style="color:red">before<br>after</span>');

        self::assertSame(['before', 'after'], $this->texts($events));
        self::assertSame(LogSeverity::Error, $events[0]->severity);
        self::assertSame(LogSeverity::Error, $events[1]->severity);
    }

    #[Test]
    public function unclosedErrorSpanColoursEveryFollowingLine(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('ok<br><span style="color:red">bad<br>alsobad');

        self::assertSame(['ok', 'bad', 'alsobad'], $this->texts($events));
        self::assertSame(LogSeverity::Info, $events[0]->severity);
        self::assertSame(LogSeverity::Error, $events[1]->severity);
        self::assertSame(LogSeverity::Error, $events[2]->severity);
    }

    /**
     * Core nests spans, and only the red one carries severity. Since every span
     * shares one closing tag, an inner ordinary span's </span> must not be read
     * as closing the red span around it — that silently ends the error run and
     * repaints the rest of the transcript as ordinary output, which also drops
     * the result from Warning back to Success.
     */
    #[Test]
    public function nestedOrdinarySpanDoesNotCloseTheEnclosingErrorSpan(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse(
            '<span style="color:red">bad <span>detail</span><br>still bad</span>'
        );

        self::assertSame(['bad detail', 'still bad'], self::texts($events));
        self::assertSame(LogSeverity::Error, $events[0]->severity);
        self::assertSame(
            LogSeverity::Error,
            $events[1]->severity,
            'the red span is still open on line 2; the inner span closed itself'
        );
    }

    /** Several ordinary spans, and one closing after the break, still pair correctly. */
    #[Test]
    public function deeplyNestedOrdinarySpansPairWithThemselves(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse(
            '<span style="color:red">a<span><span>b</span></span><br>c</span><span>d</span>'
        );

        self::assertSame(['ab', 'cd'], self::texts($events));
        self::assertSame(LogSeverity::Error, $events[0]->severity);
        // The red span closes after "c", so "d" — outside it — is ordinary. Both
        // facts have to hold at once for the pairing to be right rather than lucky.
        self::assertSame(LogSeverity::Error, $events[1]->severity);
    }

    /** An ordinary span opened before a break must not colour anything. */
    #[Test]
    public function ordinarySpanAloneNeverProducesAnError(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('<span>plain<br>also plain</span>');

        self::assertSame(['plain', 'also plain'], self::texts($events));
        self::assertSame(LogSeverity::Info, $events[0]->severity);
        self::assertSame(LogSeverity::Info, $events[1]->severity);
    }

    /** A stray close tag must not drive the counter negative and invert the state. */
    #[Test]
    public function strayClosingSpanDoesNotInvertSeverity(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('</span>plain<br>stillplain');

        self::assertSame(['plain', 'stillplain'], $this->texts($events));
        self::assertSame(LogSeverity::Info, $events[0]->severity);
        self::assertSame(LogSeverity::Info, $events[1]->severity);
    }

    #[Test]
    public function nbspPairsBecomeDepthAndLeaveTheTextClean(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('&nbsp;&nbsp;nested');

        self::assertCount(1, $events);
        self::assertSame(1, $events[0]->depth);
        self::assertSame('nested', $events[0]->plainText());
    }

    #[Test]
    public function fourNbspAreTwoLevelsOfDepth(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('&nbsp;&nbsp;&nbsp;&nbsp;deeper');

        self::assertSame(2, $events[0]->depth);
        self::assertSame('deeper', $events[0]->plainText());
    }

    /** Only whole pairs are structure; the odd one is content and must survive. */
    #[Test]
    public function oddNbspIsKeptAtTheHeadOfTheText(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('&nbsp;&nbsp;&nbsp;odd');

        self::assertSame(1, $events[0]->depth);
        self::assertSame(self::NBSP . 'odd', $events[0]->plainText());
    }

    /** ASCII space is content, not nesting — core does not indent with it. */
    #[Test]
    public function leadingAsciiSpaceIsContentNotIndent(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('   spaced');

        self::assertSame(0, $events[0]->depth);
        self::assertSame('   spaced', $events[0]->plainText());
    }

    /**
     * The documented byte-vs-character trap. Every one of these characters encodes
     * as 0xC2 followed by a continuation byte, so any byte-wise trim of the NBSP
     * "\xC2\xA0" eats the lead byte and leaves an orphan — which surfaces as U+FFFD
     * after escaping and makes json_encode() of the plain text return false.
     * Translated packs use exactly these as bullets.
     */
    #[Test]
    public function latin1SupplementBulletsSurviveIndentExtraction(): void
    {
        foreach (['«', '»', '·', '¡', '¿', '°', '±'] as $bullet) {
            $events = (new LegacyModuleReportAdapter())->parse('&nbsp;&nbsp;' . $bullet . ' item');

            self::assertCount(1, $events, "bullet {$bullet}");
            self::assertSame(1, $events[0]->depth, "bullet {$bullet}");
            self::assertSame($bullet . ' item', $events[0]->plainText(), "bullet {$bullet}");
            self::assertTrue(
                \mb_check_encoding($events[0]->plainText(), 'UTF-8'),
                "bullet {$bullet} produced invalid UTF-8"
            );
            self::assertNotFalse(
                \json_encode($events[0]->plainText()),
                "bullet {$bullet} is not JSON-encodable"
            );
        }
    }

    #[Test]
    public function daggerAtEndOfLineSurvivesIntact(): void
    {
        // "†" is U+2020 = \xE2\x80\xA0 — it ENDS in the same byte as NBSP.
        $events = (new LegacyModuleReportAdapter())->parse('note †');

        self::assertSame('note †', $events[0]->plainText());
        self::assertTrue(\mb_check_encoding($events[0]->plainText(), 'UTF-8'));
    }

    /** Markers are private to the parse; control bytes are stripped before they run. */
    #[Test]
    public function markerBytesInInputCannotForgeSeverity(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse("\x01Enot an error");

        self::assertCount(1, $events);
        self::assertSame(LogSeverity::Info, $events[0]->severity);
        self::assertSame('Enot an error', $events[0]->plainText());
        self::assertStringNotContainsString("\x01", $events[0]->plainText());
    }

    #[Test]
    public function scriptAndStyleContentIsDiscardedEntirely(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse(
            '<script>alert(1)</script>kept<style>body{color:red}</style>'
        );

        self::assertSame(['kept'], $this->texts($events));
    }

    #[Test]
    public function crlfIsNormalisedAndLeavesNoStrayCarriageReturn(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse("alpha\r\nbeta");

        self::assertSame(['alpha', 'beta'], $this->texts($events));
        foreach ($events as $event) {
            self::assertStringNotContainsString("\r", $event->plainText());
        }
    }

    #[Test]
    public function blankLinesAreDropped(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('one<br><br><br>two');

        self::assertSame(['one', 'two'], $this->texts($events));
    }

    #[Test]
    public function coreEscapingIsCollapsedExactlyOnce(): void
    {
        $events = (new LegacyModuleReportAdapter())->parse('Smith &amp; Sons &lt;tag&gt;');

        self::assertSame('Smith & Sons <tag>', $events[0]->plainText());
    }

    /**
     * The security claim of the whole feature, asserted end to end: hostile markup in
     * core's log cannot become markup in the admin page. The transcript is data; the
     * renderer writes every tag from a literal in its own file.
     */
    #[Test]
    public function hostileMarkupCannotSurviveIntoRenderedHtml(): void
    {
        $hostile = '<img src=x onerror=alert(1)>'
            . '<a href="javascript:alert(2)">click</a>'
            . '<script>alert(3)</script>'
            . '<span style="color:red">" onmouseover="alert(4)</span>';

        $html = (new LogEventHtmlRenderer())->render(
            (new LegacyModuleReportAdapter())->parse($hostile)
        );

        self::assertStringNotContainsString('<img', $html);
        self::assertStringNotContainsString('<a ', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('onerror', $html);
        self::assertStringNotContainsString('onmouseover="', $html);
        self::assertStringNotContainsString('javascript:alert', $html);
        // The only tags the renderer may emit.
        self::assertSame(
            [],
            \array_diff($this->tagNames($html), ['span', '/span', 'strong', '/strong', 'br'])
        );
    }

    #[Test]
    public function resultMapsOutcomeToTheMatchingConstructor(): void
    {
        $adapter = new LegacyModuleReportAdapter();

        $ok = $adapter->result('quotes', 'install', Outcome::Ok, 'all good');
        self::assertTrue($ok->isOk());
        self::assertSame('quotes', $ok->dirname);
        self::assertSame(['all good'], $this->texts($ok->events));

        $skipped = $adapter->result('quotes', 'install', Outcome::Skipped, '', 'Already installed');
        self::assertTrue($skipped->isSkipped());
        self::assertSame('Already installed', $skipped->reason);

        $failed = $adapter->result('quotes', 'install', Outcome::Failed, 'core said so', 'Install did not complete');
        self::assertTrue($failed->isFailed());
        self::assertSame('Install did not complete', $failed->reason);
    }

    /**
     * @param list<LogEvent> $events
     *
     * @return list<string>
     */
    private function texts(array $events): array
    {
        return \array_map(static fn (LogEvent $e): string => $e->plainText(), $events);
    }

    /** @return list<string> */
    private function tagNames(string $html): array
    {
        \preg_match_all('#<(/?[a-z0-9]+)#i', $html, $m);

        return \array_values(\array_unique($m[1]));
    }
}
