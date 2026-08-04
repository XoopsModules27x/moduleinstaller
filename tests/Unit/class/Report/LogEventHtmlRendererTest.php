<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\Report\LogEvent;
use XoopsModules\Moduleinstaller\Report\LogEventHtmlRenderer;
use XoopsModules\Moduleinstaller\Report\LogFragment;
use XoopsModules\Moduleinstaller\Report\LogSeverity;

#[CoversClass(LogEventHtmlRenderer::class)]
final class LogEventHtmlRendererTest extends TestCase
{
    private const NBSP = "\u{00A0}";

    #[Test]
    public function emptyArrayRendersEmptyString(): void
    {
        $renderer = new LogEventHtmlRenderer();

        self::assertSame('', $renderer->render([]));
    }

    #[Test]
    public function singleInfoEventRendersPlainTextWithoutSpan(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('hello world')]);

        self::assertSame('hello world', $out);
        self::assertStringNotContainsString('<span', $out);
    }

    #[Test]
    public function successWrapsInCorrectSpanClass(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('done', LogSeverity::Success)]);

        self::assertSame('<span class="installer-log-success">done</span>', $out);
    }

    #[Test]
    public function warningWrapsInCorrectSpanClass(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('careful', LogSeverity::Warning)]);

        self::assertSame('<span class="installer-log-warning">careful</span>', $out);
    }

    #[Test]
    public function errorWrapsInCorrectSpanClass(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('boom', LogSeverity::Error)]);

        self::assertSame('<span class="installer-log-error">boom</span>', $out);
    }

    #[Test]
    public function emphasisedFragmentRendersStrongInsideSpan(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $event = new LogEvent(LogSeverity::Success, [
            new LogFragment('table '),
            new LogFragment('x8a9_quotes', true),
            new LogFragment(' created'),
        ], 0);

        $out = $renderer->render([$event]);

        self::assertSame(
            '<span class="installer-log-success">table <strong>x8a9_quotes</strong> created</span>',
            $out
        );
    }

    #[Test]
    public function escapingHappensExactlyOnce(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('<b>x</b>')]);

        self::assertSame('&lt;b&gt;x&lt;/b&gt;', $out);
        self::assertStringNotContainsString('<b>', $out);
    }

    #[Test]
    public function attributeInjectionIsImpossible(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('" onload="alert(1)', LogSeverity::Error)]);

        self::assertStringContainsString('&quot;', $out);
        self::assertStringNotContainsString('<span class="installer-log-error" onload', $out);
        self::assertStringNotContainsString('onload="alert', $out);
    }

    #[Test]
    public function ampersandRoundTripsWithSingleEscape(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('a & b')]);

        self::assertSame('a &amp; b', $out);
    }

    #[Test]
    public function depthControlsNbspIndentCount(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $depth0 = $renderer->render([LogEvent::text('x', LogSeverity::Info, 0)]);
        $depth1 = $renderer->render([LogEvent::text('x', LogSeverity::Info, 1)]);
        $depth2 = $renderer->render([LogEvent::text('x', LogSeverity::Info, 2)]);

        self::assertSame('x', $depth0);
        self::assertSame(self::NBSP . self::NBSP . 'x', $depth1);
        self::assertSame(self::NBSP . self::NBSP . self::NBSP . self::NBSP . 'x', $depth2);
    }

    #[Test]
    public function indentIsOutsideSeveritySpan(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([LogEvent::text('boom', LogSeverity::Error, 1)]);

        self::assertStringStartsWith(self::NBSP . self::NBSP, $out);
        self::assertSame(
            self::NBSP . self::NBSP . '<span class="installer-log-error">boom</span>',
            $out
        );
    }

    #[Test]
    public function twoEventsAreJoinedByBrAndNewline(): void
    {
        $renderer = new LogEventHtmlRenderer();

        $out = $renderer->render([
            LogEvent::text('first'),
            LogEvent::text('second'),
        ]);

        self::assertSame("first<br>\nsecond", $out);
    }
}
