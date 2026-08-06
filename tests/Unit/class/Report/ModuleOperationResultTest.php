<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\Report\LogEvent;
use XoopsModules\Moduleinstaller\Report\LogSeverity;
use XoopsModules\Moduleinstaller\Report\ModuleOperationResult;
use XoopsModules\Moduleinstaller\Report\Outcome;

#[CoversClass(ModuleOperationResult::class)]
final class ModuleOperationResultTest extends TestCase
{
    #[Test]
    public function okFactoryProducesOkOutcome(): void
    {
        $result = ModuleOperationResult::ok('quotes', 'install');

        self::assertTrue($result->isOk());
        self::assertFalse($result->isSkipped());
        self::assertFalse($result->isFailed());
        self::assertSame(Outcome::Ok, $result->outcome);
        self::assertNull($result->reason);
    }

    #[Test]
    public function skippedFactoryProducesSingleWarningEvent(): void
    {
        $result = ModuleOperationResult::skipped('quotes', 'install', 'already installed');

        self::assertTrue($result->isSkipped());
        self::assertSame('already installed', $result->reason);
        self::assertCount(1, $result->events);
        self::assertSame(LogSeverity::Warning, $result->events[0]->severity);
        self::assertSame('already installed', $result->events[0]->plainText());
    }

    #[Test]
    public function failedFactoryPrependsReasonAsErrorEvent(): void
    {
        $transcript = [
            LogEvent::text('step one'),
            LogEvent::text('step two'),
        ];

        $result = ModuleOperationResult::failed('quotes', 'install', 'install failed', $transcript);

        self::assertCount(3, $result->events);
        self::assertSame(LogSeverity::Error, $result->events[0]->severity);
        self::assertSame('install failed', $result->events[0]->plainText());
        self::assertSame('step one', $result->events[1]->plainText());
        self::assertSame('step two', $result->events[2]->plainText());
    }

    #[Test]
    public function failedWithTranscriptDoesNotPrependOrDuplicate(): void
    {
        $transcript = [
            LogEvent::text('install failed', LogSeverity::Error),
            LogEvent::text('step two'),
        ];

        $result = ModuleOperationResult::failedWithTranscript('quotes', 'install', $transcript);

        self::assertCount(2, $result->events);
        self::assertSame('install failed', $result->reason);
        self::assertSame($result->events[0]->plainText(), $result->reason);

        $occurrences = \substr_count($result->plainText(), 'install failed');
        self::assertSame(1, $occurrences);
    }

    #[Test]
    public function failedWithTranscriptEmptyArrayYieldsEmptyReason(): void
    {
        $result = ModuleOperationResult::failedWithTranscript('quotes', 'install', []);

        self::assertSame('', $result->reason);
        self::assertSame([], $result->events);
    }

    #[Test]
    public function hasErrorEventsIsIndependentOfOutcome(): void
    {
        $okWithError = ModuleOperationResult::ok('quotes', 'install', [
            LogEvent::text('something odd', LogSeverity::Error),
        ]);
        self::assertTrue($okWithError->hasErrorEvents());

        $failedAllInfo = ModuleOperationResult::failedWithTranscript('quotes', 'install', [
            LogEvent::text('just info', LogSeverity::Info),
        ]);
        self::assertFalse($failedAllInfo->hasErrorEvents());
    }

    #[Test]
    public function severityFailedIsError(): void
    {
        $result = ModuleOperationResult::failed('quotes', 'install', 'boom');

        self::assertSame(LogSeverity::Error, $result->severity());
    }

    #[Test]
    public function severitySkippedIsWarning(): void
    {
        $result = ModuleOperationResult::skipped('quotes', 'install', 'already there');

        self::assertSame(LogSeverity::Warning, $result->severity());
    }

    #[Test]
    public function severityOkWithErrorEventIsWarning(): void
    {
        $result = ModuleOperationResult::ok('quotes', 'install', [
            LogEvent::text('odd but survived', LogSeverity::Error),
        ]);

        self::assertSame(LogSeverity::Warning, $result->severity());
    }

    #[Test]
    public function severityOkWithCleanEventsIsSuccess(): void
    {
        $result = ModuleOperationResult::ok('quotes', 'install', [
            LogEvent::text('all good', LogSeverity::Success),
        ]);

        self::assertSame(LogSeverity::Success, $result->severity());
    }

    #[Test]
    public function plainTextJoinsEventPlainTextWithNewline(): void
    {
        $result = ModuleOperationResult::ok('quotes', 'install', [
            LogEvent::text('first'),
            LogEvent::text('second'),
        ]);

        self::assertSame("first\nsecond", $result->plainText());
    }

    #[Test]
    public function toArrayHasExactKeysAndEnumStringValues(): void
    {
        $result = ModuleOperationResult::failed('quotes', 'install', 'install failed', [
            LogEvent::text('step one', LogSeverity::Info, 1),
        ]);

        $array = $result->toArray();

        self::assertSame(
            ['dirname', 'action', 'outcome', 'severity', 'reason', 'events'],
            \array_keys($array)
        );
        self::assertSame('quotes', $array['dirname']);
        self::assertSame('install', $array['action']);
        self::assertSame('fail', $array['outcome']);
        self::assertSame('error', $array['severity']);
        self::assertSame('install failed', $array['reason']);

        self::assertCount(2, $array['events']);
        foreach ($array['events'] as $eventArray) {
            self::assertSame(['severity', 'depth', 'text'], \array_keys($eventArray));
        }

        self::assertSame('error', $array['events'][0]['severity']);
        self::assertSame(0, $array['events'][0]['depth']);
        self::assertSame('install failed', $array['events'][0]['text']);

        self::assertSame('info', $array['events'][1]['severity']);
        self::assertSame(1, $array['events'][1]['depth']);
        self::assertSame('step one', $array['events'][1]['text']);
    }
}
