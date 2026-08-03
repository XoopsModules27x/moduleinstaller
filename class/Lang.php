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

/**
 * Translated text for a language constant, with an English fallback.
 *
 * Every user-visible string in this module comes from a language constant, and every
 * one of them can legitimately be missing: a module class can run before any language
 * file was loaded (ModuleActionService::runOne() returns for an empty dirname before
 * loadCoreAdmin()), and a partial third-party language pack can simply not define a
 * constant the code expects. On PHP 8 an undefined constant is a fatal `Error`, not a
 * notice, so "just reference the constant" turns a missing translation into a blank
 * admin page — which is what this class exists to prevent.
 *
 * Two domains, because the constants come from two places:
 *
 *   text()/format()  →  this module's language/<lang>/admin.php   (_AM_MODULEINSTALLER_*)
 *   core()           →  core's language/<lang>/global.php         (_YES, _NO, …)
 *
 * Each domain is loaded lazily, at most once per request, on the first lookup that
 * misses. Loading goes through Xmf\Language when available — it resolves
 * $xoopsConfig['language'], falls back to english, and refuses paths outside
 * XOOPS_ROOT_PATH — and through xoops_loadLanguage() otherwise. Both APIs are
 * long-standing, so this works on XOOPS 2.7.x and 2.8+ alike; the only real floor is
 * the module's documented PHP 8.2.
 *
 * NOTE: this returns TEXT, never markup. Escaping stays at the call site, because the
 * caller is the only one that knows whether the string is going into an element, an
 * attribute or a JS literal.
 */
final class Lang
{
    /** This module's dirname, for the language domain. */
    private const DIRNAME = 'moduleinstaller';

    /** Lazy load is attempted at most once per domain per request. */
    private static bool $moduleAttempted = false;
    private static bool $globalAttempted = false;

    /**
     * Text for one of this module's own constants.
     *
     * @param string $constant e.g. _AM_MODULEINSTALLER_SUBMIT
     * @param string $fallback English text used when the constant is not defined
     */
    public static function text(string $constant, string $fallback): string
    {
        if (! \defined($constant)) {
            self::loadModule();
        }

        return self::usable($constant) ?? $fallback;
    }

    /**
     * The constant's value, or null when it cannot serve as a translation.
     *
     * "Defined" is not the same as "translated". Three states all mean fall back:
     *
     *   - not defined at all — the usual partial-pack case;
     *   - defined as '' or as whitespace, which a half-finished pack produces in bulk
     *     and which renders as an invisible button or an empty table header. A blank
     *     label is not a deliberate translation choice often enough to be worth
     *     honouring, and English text in an Arabic admin is recoverable where a
     *     missing control is not;
     *   - defined as something that is not SCALAR. Casting an array to string in PHP 8
     *     emits a warning and yields the literal "Array"; a language file is ordinary
     *     PHP, so this is reachable by a typo rather than by malice.
     *
     * The rule is therefore "scalar, and non-blank after trim()" — not "must be a
     * string". Two consequences are deliberate and easy to get backwards:
     *
     *   - an int, a float or `true` IS used. A count or a version number legitimately
     *     arrives as a non-string scalar, and rejecting those would send a pack back to
     *     English over a missing pair of quotes;
     *   - `false` is NOT used, even though it is scalar, because it casts to '' — and
     *     '0' IS used, because it is a real translation of a real string and only looks
     *     empty. The blankness test is on the cast value, which gets both of these
     *     right for the same reason.
     */
    private static function usable(string $constant): ?string
    {
        if (! \defined($constant)) {
            return null;
        }

        $value = \constant($constant);
        if (! \is_scalar($value)) {
            return null;
        }

        $value = (string) $value;

        return '' === \trim($value) ? null : $value;
    }

    /**
     * Same as {@see self::text()} for a message with sprintf() placeholders.
     *
     * Formatting belongs here so a translation may reorder its arguments with
     * %1$s/%2$s without the caller knowing or caring.
     */
    public static function format(string $constant, string $fallback, int|float|string ...$args): string
    {
        $template = self::text($constant, $fallback);

        // sprintf() throws on PHP 8 when a template asks for more arguments than it
        // is given, or uses an unknown conversion — and the template here comes from
        // a translator, not from this codebase. Letting that propagate would take the
        // admin page down over a typo in a language pack, which is the exact failure
        // this class exists to prevent: it would make a bad translation worse than a
        // missing one. Fall back to the English text, which is known to match $args.
        try {
            return \sprintf($template, ...$args);
        } catch (\Throwable) {
            try {
                return \sprintf($fallback, ...$args);
            } catch (\Throwable) {
                // Even the fallback disagrees with the caller: return it unformatted
                // rather than nothing, so the admin still sees a readable message.
                return $fallback;
            }
        }
    }

    /**
     * Text for a CORE constant from the global language file (_YES, _NO, …).
     *
     * Separate from {@see self::text()} because a missing _YES is not fixed by
     * loading this module's admin.php — it is a different file in a different domain.
     */
    public static function core(string $constant, string $fallback): string
    {
        if (! \defined($constant)) {
            self::loadGlobal();
        }

        // Same usability policy as text(). The two domains differ in WHERE the string
        // comes from, not in what counts as a string: _YES from a half-translated core
        // pack fails exactly the way _AM_MODULEINSTALLER_SUBMIT does, and a policy that
        // applied to one domain and not the other would be a policy nobody could state.
        return self::usable($constant) ?? $fallback;
    }

    private static function loadModule(): void
    {
        if (self::$moduleAttempted) {
            return;
        }
        self::$moduleAttempted = true;
        self::load('admin', self::DIRNAME);
    }

    private static function loadGlobal(): void
    {
        if (self::$globalAttempted) {
            return;
        }
        self::$globalAttempted = true;
        self::load('global', '');
    }

    /**
     * Prefer Xmf\Language::load(): it picks the configured language, falls back to
     * english on its own, and validates the path. xoops_loadLanguage() is the
     * fallback for a core build without Xmf.
     */
    private static function load(string $name, string $domain): void
    {
        if (\class_exists(\Xmf\Language::class)) {
            \Xmf\Language::load($name, $domain);

            return;
        }

        if (\function_exists('xoops_loadLanguage')) {
            \xoops_loadLanguage($name, $domain);
        }
    }
}
