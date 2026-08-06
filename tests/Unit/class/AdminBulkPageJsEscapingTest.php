<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\AdminBulkPage;

/**
 * Escaping contract for inline event handlers.
 *
 * A browser HTML-decodes an attribute value BEFORE compiling it as JavaScript, so
 * an HTML escaper is the wrong tool inside onclick: an entity-escaped apostrophe
 * decodes back to a bare quote and closes the string literal. Every assertion here
 * therefore decodes the attribute first and inspects what the JS parser would
 * actually see — testing the raw markup would pass against the bug.
 */
#[CoversClass(AdminBulkPage::class)]
final class AdminBulkPageJsEscapingTest extends TestCase
{
    #[Test]
    public function benignDirnameProducesTheExpectedCall(): void
    {
        $html = AdminBulkPage::renderYesNoRadios('quotes', 1);

        self::assertSame('selectModule("quotes", this)', $this->firstOnclick($html));
    }

    /** @return iterable<string, array{string}> */
    public static function breakoutAttempts(): iterable
    {
        yield 'apostrophe breakout' => ["foo'); alert(1);//"];
        yield 'double quote breakout' => ['foo"); alert(1);//'];
        yield 'script tag' => ['foo<script>alert(1)</script>'];
        yield 'ampersand entity' => ['foo&#039;); alert(1);//'];
        yield 'double encoded' => ['foo&amp;#039;); alert(1);//'];
        yield 'closing attribute' => ['foo" onmouseover="alert(1)'];
        yield 'backslash' => ['foo\\"); alert(1);//'];
        yield 'newline' => ["foo\n); alert(1);//"];
    }

    /**
     * The decisive assertion: after the browser decodes the attribute, the payload
     * must still be INSIDE the JS string literal. If it escaped, the decoded text
     * would contain a bare quote followed by the injected call.
     */
    #[Test]
    #[DataProvider('breakoutAttempts')]
    public function hostileDirnameCannotEscapeTheJsStringLiteral(string $dirname): void
    {
        $decoded = $this->firstOnclick(AdminBulkPage::renderYesNoRadios($dirname, 0));

        // Exactly one call, with exactly two arguments, and the payload contained.
        self::assertMatchesRegularExpression(
            '/^selectModule\("(?:[^"\\\\]|\\\\.)*", this\)$/',
            $decoded,
            'handler is not a single well-formed call: ' . $decoded
        );
        // No raw quote of either kind can appear inside the literal body.
        $body = \substr($decoded, \strlen('selectModule("'), -\strlen('", this)'));
        self::assertStringNotContainsString("'", $body);
        self::assertStringNotContainsString('"', $body);
        self::assertStringNotContainsString('<', $body);
    }

    /** Both radios must carry the same handler — the bug was per-attribute, not per-row. */
    #[Test]
    public function bothRadiosCarryTheIdenticalSafeHandler(): void
    {
        $html = AdminBulkPage::renderYesNoRadios("foo'); alert(1);//", 1);

        \preg_match_all('/onclick="([^"]*)"/', $html, $m);
        self::assertCount(2, $m[1], 'expected a handler on each radio');
        self::assertSame($m[1][0], $m[1][1]);
    }

    /** The element id is a separate, HTML-side concern and stays sanitised. */
    #[Test]
    public function elementIdIsSanitisedIndependently(): void
    {
        $html = AdminBulkPage::renderYesNoRadios("foo'); alert(1);//", 1);

        self::assertStringContainsString('id="modsel_foo____alert_1_____1"', $html);
        self::assertStringNotContainsString('id="modsel_foo\'', $html);
    }

    /** Invalid UTF-8 yields an inert handler rather than a malformed one. */
    #[Test]
    public function invalidUtf8DirnameYieldsAnInertHandler(): void
    {
        $decoded = $this->firstOnclick(AdminBulkPage::renderYesNoRadios("bad\xC3\x28name", 0));

        self::assertSame('void 0', $decoded);
    }

    /** The onclick attribute value as the JavaScript parser would receive it. */
    private function firstOnclick(string $html): string
    {
        if (1 !== \preg_match('/onclick="([^"]*)"/', $html, $m)) {
            self::fail('no onclick attribute found in: ' . $html);
        }

        return \html_entity_decode($m[1], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
    }
}
