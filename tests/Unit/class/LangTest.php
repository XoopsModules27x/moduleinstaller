<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\Lang;

/**
 * The fallback policy, as executable cases.
 *
 * Every branch here is a way a real language pack fails: absent, half-translated,
 * blank, typo'd into a non-string, or formatted with the wrong placeholders. The
 * class exists so that none of them can blank an admin page, which is a claim only
 * a test can hold.
 */
#[CoversClass(Lang::class)]
final class LangTest extends TestCase
{
    private const NBSP = "\u{00A0}";

    #[Test]
    public function undefinedConstantFallsBackToEnglish(): void
    {
        self::assertSame(
            'Install',
            Lang::text('_AM_MODULEINSTALLER_TEST_NEVER_DEFINED', 'Install')
        );
    }

    #[Test]
    public function definedConstantIsUsed(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_REAL', 'Installer');

        self::assertSame('Installer', Lang::text('_AM_MODULEINSTALLER_TEST_REAL', 'Install'));
    }

    #[Test]
    public function emptyConstantFallsBack(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_EMPTY', '');

        self::assertSame('Submit', Lang::text('_AM_MODULEINSTALLER_TEST_EMPTY', 'Submit'));
    }

    #[Test]
    public function whitespaceOnlyConstantFallsBack(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_BLANK', "  \t\n ");

        self::assertSame('Submit', Lang::text('_AM_MODULEINSTALLER_TEST_BLANK', 'Submit'));
    }

    /**
     * The case trim() alone gets wrong: a lone NBSP is what a translation tool emits
     * for "intentionally empty", and it renders as invisibly as a space.
     */
    #[Test]
    public function nbspOnlyConstantFallsBack(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_NBSP', self::NBSP);

        self::assertSame('Submit', Lang::text('_AM_MODULEINSTALLER_TEST_NBSP', 'Submit'));
    }

    #[Test]
    public function mixedUnicodeBlanksFallBack(): void
    {
        // NBSP + ASCII space + figure space (U+2007) + BOM (U+FEFF)
        \define('_AM_MODULEINSTALLER_TEST_BLANKS', self::NBSP . ' ' . "\u{2007}" . "\u{FEFF}");

        self::assertSame('Submit', Lang::text('_AM_MODULEINSTALLER_TEST_BLANKS', 'Submit'));
    }

    /**
     * The regression guard for the fix that was NOT applied: widening trim()'s
     * charlist to "\xC2\xA0" is byte-wise, and "†" (U+2020) encodes as \xE2\x80\xA0.
     * A byte trim would eat its final byte and hand back invalid UTF-8.
     */
    #[Test]
    public function valueEndingInADaggerIsReturnedIntact(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_DAGGER', 'Note †');

        $value = Lang::text('_AM_MODULEINSTALLER_TEST_DAGGER', 'Note');

        self::assertSame('Note †', $value);
        self::assertTrue(\mb_check_encoding($value, 'UTF-8'));
        self::assertNotFalse(\json_encode($value));
    }

    /** A leading NBSP is content when real text follows it — not something to strip. */
    #[Test]
    public function leadingNbspIsPreservedWhenTextFollows(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_LEAD_NBSP', self::NBSP . 'Indented');

        self::assertSame(
            self::NBSP . 'Indented',
            Lang::text('_AM_MODULEINSTALLER_TEST_LEAD_NBSP', 'Indented')
        );
    }

    /** '0' is a real translation that only looks empty. */
    #[Test]
    public function zeroStringIsUsed(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_ZERO', '0');

        self::assertSame('0', Lang::text('_AM_MODULEINSTALLER_TEST_ZERO', 'none'));
    }

    /** false casts to '' and is therefore blank, even though it is scalar. */
    #[Test]
    public function falseConstantFallsBack(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_FALSE', false);

        self::assertSame('Yes', Lang::text('_AM_MODULEINSTALLER_TEST_FALSE', 'Yes'));
    }

    #[Test]
    public function nonStringScalarsAreUsed(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_INT', 42);
        \define('_AM_MODULEINSTALLER_TEST_TRUE', true);

        self::assertSame('42', Lang::text('_AM_MODULEINSTALLER_TEST_INT', 'x'));
        self::assertSame('1', Lang::text('_AM_MODULEINSTALLER_TEST_TRUE', 'x'));
    }

    /** A typo in a language file, not malice — and casting it would yield "Array". */
    #[Test]
    public function nonScalarConstantFallsBack(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_ARRAY', ['a', 'b']);

        self::assertSame('Submit', Lang::text('_AM_MODULEINSTALLER_TEST_ARRAY', 'Submit'));
    }

    /** Invalid UTF-8 is left alone: the pattern cannot judge it, so it is not rejected. */
    #[Test]
    public function invalidUtf8ConstantIsStillReturned(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_BADUTF8', "bad\xC3\x28value");

        self::assertSame(
            "bad\xC3\x28value",
            Lang::text('_AM_MODULEINSTALLER_TEST_BADUTF8', 'fallback')
        );
    }

    #[Test]
    public function formatSubstitutesArguments(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_FMT', 'folder: /%s');

        self::assertSame(
            'folder: /quotes',
            Lang::format('_AM_MODULEINSTALLER_TEST_FMT', 'folder: /%s', 'quotes')
        );
    }

    #[Test]
    public function formatUsesTheFallbackTemplateWhenTheConstantIsMissing(): void
    {
        self::assertSame(
            'Saved snapshot: a (b)',
            Lang::format('_AM_MODULEINSTALLER_TEST_FMT_MISSING', 'Saved snapshot: %1$s (%2$s)', 'a', 'b')
        );
    }

    /** A translator may reorder placeholders; the caller neither knows nor cares. */
    #[Test]
    public function formatHonoursReorderedPlaceholders(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_FMT_SWAP', '%2$s <- %1$s');

        self::assertSame(
            'second <- first',
            Lang::format('_AM_MODULEINSTALLER_TEST_FMT_SWAP', '%1$s -> %2$s', 'first', 'second')
        );
    }

    /**
     * A template that asks for more arguments than it is given throws on PHP 8. That
     * must degrade to English, not take the page down.
     */
    #[Test]
    public function formatFallsBackWhenTheTranslationDemandsTooManyArguments(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_FMT_GREEDY', 'wants %1$s and %2$s');

        self::assertSame(
            'got one',
            Lang::format('_AM_MODULEINSTALLER_TEST_FMT_GREEDY', 'got %s', 'one')
        );
    }

    /** When even the English template disagrees, return it raw rather than nothing. */
    #[Test]
    public function formatReturnsTheRawFallbackWhenBothTemplatesAreWrong(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_FMT_BOTHBAD', 'wants %1$s and %2$s');

        self::assertSame(
            'also wants %2$s',
            Lang::format('_AM_MODULEINSTALLER_TEST_FMT_BOTHBAD', 'also wants %2$s', 'one')
        );
    }

    /**
     * core() reads the other domain but applies the same usability policy. With no
     * bootable XOOPS the global file cannot load, which is exactly the situation the
     * fallback is for — and it must return, not throw.
     */
    #[Test]
    public function coreConstantFallsBackWithoutABootedXoops(): void
    {
        self::assertSame('Yes', Lang::core('_AM_MODULEINSTALLER_TEST_CORE_MISSING', 'Yes'));
    }

    #[Test]
    public function coreAppliesTheSameBlanknessPolicy(): void
    {
        \define('_AM_MODULEINSTALLER_TEST_CORE_NBSP', self::NBSP);

        self::assertSame('No', Lang::core('_AM_MODULEINSTALLER_TEST_CORE_NBSP', 'No'));
    }
}
