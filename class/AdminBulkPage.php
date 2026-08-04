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

use Xmf\Module\Admin;
use Xmf\Request;
use XoopsModules\Moduleinstaller\Set\ModuleSetRepository;

/**
 * Shared bulk install/uninstall/activate/deactivate/update admin pages (CP chrome only).
 *
 * Thin wrappers (install.php, …) call {@see self::serve()}.
 */
final class AdminBulkPage
{
    /**
     * Cache-bust query for admin CSS/JS.
     *
     * Bump this in the SAME commit as any CSS/JS change: an admin holding the
     * previous stylesheet otherwise sees none of it, which is how a CSS-only
     * improvement can measure as no improvement at all.
     */
    public const ASSET_VERSION = '1715';

    /**
     * Full bulk page after xoops_cp_header() — navigation, list or report, form, assets.
     *
     * @param string $action One of ModuleActionService::ACTION_*
     */
    public static function serve(string $action): void
    {
        self::ensureAssets();
        $action = \mb_strtolower(\trim($action));
        $navScript = \basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'install.php'));

        $adminObject = Admin::getInstance();
        $adminObject->displayNavigation($navScript);

        $pageHasForm = true;
        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            if (! self::checkCsrf()) {
                $pageHasForm = false;
                $msg = Lang::text('_AM_MODULEINSTALLER_ERR_TOKEN', 'Invalid security token. Please try again.');
                $content = "<div class='errorMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
            } else {
                $handled = self::handlePost($action, self::successTitleFor($action));
                $pageHasForm = $handled['pageHasForm'];
                $content = $handled['content'];
            }
        } else {
            $built = self::buildListPage($action);
            $content = $built['content'];
            $pageHasForm = $built['pageHasForm'];
        }

        if ($pageHasForm) {
            self::displaySelectionControls(true);
            echo self::wrapBulkForm($content, $navScript);
        } else {
            echo $content;
            echo '<p class="installer-back-link"><a class="formButton" href="index.php">'
                . \htmlspecialchars(
                    Lang::text('_AM_MODULEINSTALLER_BACK_HOME', 'Installer home'),
                    \ENT_QUOTES | \ENT_SUBSTITUTE,
                    'UTF-8'
                )
                . '</a></p>';
        }
    }

    /**
     * Load admin CSS/JS once (call after xoops_cp_header so xoTheme exists).
     */
    public static function ensureAssets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        if (! isset($GLOBALS['xoTheme']) || ! \is_object($GLOBALS['xoTheme'])) {
            return;
        }
        $dirname = 'moduleinstaller';
        if (isset($GLOBALS['xoopsModule']) && \is_object($GLOBALS['xoopsModule'])) {
            $dirname = (string) $GLOBALS['xoopsModule']->getVar('dirname');
        }
        $base = \XOOPS_URL . '/modules/' . $dirname . '/assets';
        $v = self::ASSET_VERSION;
        // Scoped module UX only — CP look & feel comes from ModuleAdmin + admin theme
        $GLOBALS['xoTheme']->addStylesheet($base . '/css/admin.css?v=' . $v);
        $GLOBALS['xoTheme']->addScript($base . '/js/xo-installer.js?v=' . $v);
        $loaded = true;
    }

    /**
     * Build GET list content for an action.
     *
     * @return array{content: string, pageHasForm: bool}
     */
    public static function buildListPage(string $action): array
    {
        $catalog = new ModuleCatalog();
        $action = \mb_strtolower(\trim($action));

        if (ModuleActionService::ACTION_UPDATE === $action) {
            return self::buildUpdateListPage($catalog);
        }

        $list = $catalog->candidatesFor($action);
        if ([] === $list) {
            return [
                'content' => self::emptyListMessage(),
                'pageHasForm' => false,
            ];
        }

        return [
            'content' => self::renderModuleTable($list),
            'pageHasForm' => true,
        ];
    }

    /**
     * @return array{content: string, pageHasForm: bool}
     */
    private static function buildUpdateListPage(ModuleCatalog $catalog): array
    {
        $list = $catalog->candidatesFor(ModuleActionService::ACTION_UPDATE);
        $needs = $catalog->listNeedsUpdate();
        $onlyNeeds = Request::getInt('needs_only', 0, 'GET') === 1;
        if ($onlyNeeds) {
            $list = $needs;
        }

        if ([] === $list) {
            return [
                'content' => self::emptyListMessage(),
                'pageHasForm' => false,
            ];
        }

        $content = '';
        if ($onlyNeeds) {
            // Was a raw constant reference. On PHP 8 an undefined constant is a fatal
            // Error, so a partial language pack turned this page blank.
            $content .= "<div class='x2-note confirmMsg'>"
                . \htmlspecialchars(
                    Lang::text('_AM_MODULEINSTALLER_PRESELECTED_UPDATES', 'Pre-selected modules whose version on disk differs from the database.'),
                    \ENT_QUOTES | \ENT_SUBSTITUTE,
                    'UTF-8'
                ) . '</div>';
        }
        $toggleUrl = 'update.php' . ($onlyNeeds ? '' : '?needs_only=1');
        $toggleLbl = Lang::text('_AM_MODULEINSTALLER_SHOW_UPDATES_ONLY', 'Show only modules that need update');
        $content .= '<p><a class="formButton" href="' . \htmlspecialchars($toggleUrl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '">'
            . \htmlspecialchars($toggleLbl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            . ($onlyNeeds ? ' ✓' : '') . '</a></p>';
        $content .= self::renderModuleTable($list, $needs, static fn (string $dirname, array $info): array => ['highlight' => $catalog->needsUpdate($dirname)]);

        return [
            'content' => $content,
            'pageHasForm' => true,
        ];
    }

    private static function emptyListMessage(): string
    {
        $msg = Lang::text('_AM_MODULEINSTALLER_NO_MODULES', 'No modules found.');

        return "<div class='x2-note confirmMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    }

    private static function successTitleFor(string $action): string
    {
        $map = [
            ModuleActionService::ACTION_INSTALL => '_AM_MODULEINSTALLER_DONE_INSTALL',
            ModuleActionService::ACTION_UNINSTALL => '_AM_MODULEINSTALLER_DONE_UNINSTALL',
            ModuleActionService::ACTION_ACTIVATE => '_AM_MODULEINSTALLER_DONE_ACTIVATE',
            ModuleActionService::ACTION_DEACTIVATE => '_AM_MODULEINSTALLER_DONE_DEACTIVATE',
            ModuleActionService::ACTION_UPDATE => '_AM_MODULEINSTALLER_DONE_UPDATE',
        ];
        $const = $map[$action] ?? '';
        $fallback = \ucfirst($action) . ' complete';

        return '' === $const ? $fallback : Lang::text($const, $fallback);
    }

    private static function checkCsrf(): bool
    {
        if (! isset($GLOBALS['xoopsSecurity']) || ! \is_object($GLOBALS['xoopsSecurity'])) {
            // Fail closed: without the security service we cannot validate the CSRF
            // token, and these endpoints perform destructive bulk actions.
            return false;
        }

        return (bool) $GLOBALS['xoopsSecurity']->check();
    }

    private static function wrapBulkForm(string $innerHtml, string $actionScript): string
    {
        $actionEsc = \htmlspecialchars($actionScript, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        $submitLbl = \htmlspecialchars(
            Lang::text('_AM_MODULEINSTALLER_SUBMIT', 'Continue'),
            \ENT_QUOTES | \ENT_SUBSTITUTE,
            'UTF-8'
        );
        $token = '';
        if (isset($GLOBALS['xoopsSecurity']) && \is_object($GLOBALS['xoopsSecurity'])) {
            $token = $GLOBALS['xoopsSecurity']->getTokenHTML();
        }

        return '<form id="installer-bulk-form" class="installer-bulk-form" action="' . $actionEsc . '" method="post">'
            . $token
            . $innerHtml
            . '<div class="installer-form-actions">'
            . '<button type="submit" class="formButton">' . $submitLbl . '</button>'
            . '</div></form>';
    }

    /**
     * Selected modules map from POST (dirname => 0|1).
     *
     * @return array<string, mixed>
     */
    public static function postedModules(): array
    {
        $modules = Request::getArray('modules', [], 'POST');

        return \is_array($modules) ? $modules : [];
    }

    /**
     * Build Yes/No module selection table HTML.
     *
     * @param list<string>  $dirnames      Dirnames to list
     * @param list<string>  $preselect     Dirnames pre-selected as Yes
     * @param callable|null $rowDecorator  Optional (dirname, infoArray) => array{highlight?:bool,label_style?:string}
     */
    public static function renderModuleTable(array $dirnames, array $preselect = [], ?callable $rowDecorator = null): string
    {
        $catalog = new ModuleCatalog();
        $preselect = \array_fill_keys($preselect, true);
        $filterPh = Lang::text('_AM_MODULEINSTALLER_FILTER_PLACEHOLDER', 'Filter…');
        $filterLbl = Lang::text('_AM_MODULEINSTALLER_FILTER', 'Filter');
        $filterHint = Lang::text('_AM_MODULEINSTALLER_FILTER_HINT', '');
        $noneMsg = Lang::text('_AM_MODULEINSTALLER_SET_FILTER_NONE', 'No match');

        $selectAllLbl = \htmlspecialchars(
            Lang::text('_AM_MODULEINSTALLER_SELECT_ALL', 'Select All'),
            \ENT_QUOTES | \ENT_SUBSTITUTE,
            'UTF-8'
        );
        $selectNoneLbl = \htmlspecialchars(
            Lang::text('_AM_MODULEINSTALLER_SELECT_NONE', 'Un-Select All'),
            \ENT_QUOTES | \ENT_SUBSTITUTE,
            'UTF-8'
        );
        $actionButtons = '<button type="button" class="formButton installer-btn" onclick="selectAll(); return false;">'
            . $selectAllLbl . '</button> '
            . '<button type="button" class="formButton installer-btn" onclick="unselectAll(); return false;">'
            . $selectNoneLbl . '</button>';

        // Select All / Un-Select All: ModuleAdmin buttons above + sticky bar below.
        // Selected count is shown on the page title (.CPbigTitle) and sticky bar only.
        $content = '<div class="installer-list-toolbar">';
        $content .= '<label class="installer-filter-label" for="installer-module-filter">'
            . \htmlspecialchars($filterLbl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</label> ';
        // type=text (not search) so theme/input styles apply; size gives a visible fallback width
        $content .= '<input type="text" id="installer-module-filter" class="installer-module-filter form-control" '
            . 'size="36" '
            . 'placeholder="' . \htmlspecialchars($filterPh, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '" '
            . 'autocomplete="off" aria-label="' . \htmlspecialchars($filterLbl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '">';
        if ('' !== $filterHint) {
            $content .= ' <span class="small installer-filter-hint">'
                . \htmlspecialchars($filterHint, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        }
        $content .= '</div>';
        $content .= '<p id="installer-filter-empty" class="x2-note confirmMsg installer-hidden">'
            . \htmlspecialchars($noneMsg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        // No ul/li wrapper: list markers broke first-row radio layout (black square + stretched Yes/No)
        $content .= "<div class='installer-module-list'>"
            . "<table class='outer module installer-module-table width100'>\n"
            . "<colgroup><col class='installer-col-img'><col class='installer-col-desc'><col class='installer-col-yesno'></colgroup>\n";
        $count = 0;
        $even = false;

        // Row-invariant: the same lookup and the same escape for every row.
        $toggleTitle = \htmlspecialchars(
            Lang::text('_AM_MODULEINSTALLER_TOGGLE_SELECTION', 'Toggle selection'),
            \ENT_QUOTES | \ENT_SUBSTITUTE,
            'UTF-8'
        );

        foreach ($dirnames as $file) {
            $file = \trim((string) $file);
            if ('' === $file) {
                continue;
            }
            $info = $catalog->loadInfo($file);
            if (null === $info) {
                continue;
            }

            $value = isset($preselect[$file]) ? 1 : 0;

            $highlight = false;
            if (null !== $rowDecorator) {
                $meta = $rowDecorator($file, $info);
                if (\is_array($meta) && (bool) ($meta['highlight'] ?? false)) {
                    $highlight = true;
                }
            }

            // "needs update" is a warning, not an error, and it is a severity — so it
            // uses the same palette class the transcript does rather than an inline
            // colour that no theme or stylesheet can override.
            $spanOpen = $highlight ? '<span class="installer-log-warning">' : '<span>';
            $spanClose = '</span>';

            // Same resolver as the report: proves the target is a local file inside
            // the module before building a URL from a manifest value. Concatenating
            // $info['image'] straight onto XOOPS_URL — which this line used to do —
            // escapes correctly but validates nothing, so two code paths disagreed
            // about how much the manifest is trusted.
            $imgSrc = self::moduleLogoUrl($file, $info) ?? '';
            $dirnameEsc = \htmlspecialchars($file, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
            $nameEsc = \htmlspecialchars($info['name'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

            // The dirname lands in two different contexts and needs two different
            // escapers. htmlspecialchars() is right for id='…'; it is WRONG inside the
            // onclick JS string literal, because the browser HTML-decodes an attribute
            // value BEFORE compiling it as JavaScript — so &#039; arrives at the JS
            // parser as a bare quote and closes the literal. Core applies no character
            // filter here (XoopsLists::getDirListAsArray() skips only dot-entries and
            // 'cvs'), so a directory named  foo'); alert(1);//  reaches this line
            // verbatim. json_encode() emits a complete, already-quoted JS literal, and
            // the HEX flags keep <, >, &, ' and " out of the attribute altogether.
            $dirnameJs = \json_encode(
                $file,
                \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT
            );
            // false only for invalid UTF-8. An inert handler beats a malformed one.
            $toggleJs = false === $dirnameJs
                ? 'void 0'
                : 'toggleModuleRow(' . $dirnameJs . ')';
            $searchBlob = \htmlspecialchars(
                \mb_strtolower($info['name'] . ' ' . $file . ' ' . $info['description']),
                \ENT_QUOTES | \ENT_SUBSTITUTE,
                'UTF-8'
            );
            $even = ! $even;
            $stripe = $even ? 'even' : 'odd';
            $rowClass = \trim($stripe . ($value !== 0 ? ' installer-row-selected' : ''));
            $content .= "<tr id='" . $dirnameEsc . "' class='" . $rowClass . "' data-search=\"" . $searchBlob . '"' . ">\n";
            $content .= "    <td class='img installer-mod-toggle' onclick=\"" . $toggleJs . "\" title='" . $toggleTitle . "'>";
            // alt='' for the same reason renderReport() uses it: the module name is
            // rendered in the very next cell, so a described image makes a screen
            // reader announce the name twice on every row. The logo is decorative.
            $content .= '' !== $imgSrc
                ? "<img class='installer-mod-logo' src='" . \htmlspecialchars($imgSrc, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                    . "' alt=''>"
                : "<span class='installer-mod-logo'></span>";
            $content .= "</td>\n";
            $content .= "    <td class='installer-mod-toggle installer-mod-desc' onclick=\"" . $toggleJs . "\" title='" . $toggleTitle . "'>" . $spanOpen;
            // The version, the status and the folder path are an LTR run that may sit
            // beside an RTL module name. Each is isolated so the parentheses and the
            // digits cannot migrate across the boundary.
            $content .= '        ' . $nameEsc
                . '&nbsp;<bdi>' . \htmlspecialchars((string) $info['version'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</bdi>'
                . '&nbsp;<bdi>' . \htmlspecialchars((string) $info['module_status'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</bdi>'
                . '&nbsp;<bdi>(' . \htmlspecialchars(
                    Lang::format('_AM_MODULEINSTALLER_FOLDER_LABEL', 'folder: /%s', $info['dirname']),
                    \ENT_QUOTES | \ENT_SUBSTITUTE,
                    'UTF-8'
                ) . ')</bdi>';
            $content .= '        <br><span class="small installer-mod-desc-text">'
                . \htmlspecialchars($info['description'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</span>';
            $content .= $spanClose . "</td>\n";
            $content .= "    <td class='yesno'>" . self::renderYesNoRadios($file, $value) . "</td></tr>\n";
            ++$count;
        }

        $content .= '</table></div>';
        $content .= self::renderStickyBar($actionButtons);

        return 0 === $count ? self::emptyListMessage() : $content;
    }

    /**
     * Escaped text that goes before and after the live selection counter.
     *
     * Returns DATA, not markup, and takes no markup: an earlier version accepted the
     * counter element as a string parameter and concatenated it verbatim, which made
     * a public method whose second argument is trusted HTML — a trap for any caller
     * that passes something user-derived. The counter element is built by the one
     * caller that owns it; this method only decides where the text splits.
     *
     * Pure and public so it can be tested directly: the count itself is written by
     * JavaScript, so none of this is reachable through a rendered page.
     *
     * Three things the naive split gets wrong, all of them translator-facing:
     *   - '%1$d selected' and '%s ausgewählt' are valid translations of a '%d'
     *     string, and explode('%d') matches neither;
     *   - '%%' is a LITERAL percent, not a placeholder, so '50%% of %d' must split
     *     at the second construct and render "50% of";
     *   - this string takes exactly ONE argument, so a translation carrying a second
     *     placeholder is a mistake — and showing an admin a raw '%2$d' is worse than
     *     dropping it. Dropping it takes the space in front of it too, or
     *     '%1$d: %2$s selected' would render ':  selected' with a doubled gap.
     *
     * @param string $template language string containing one placeholder
     *
     * @return array{0:string, 1:string} escaped prefix and suffix
     */
    public static function selectedCountParts(string $template): array
    {
        // (*SKIP)(*FAIL) makes the engine step over '%%' without matching it.
        $placeholder = '/%%(*SKIP)(*FAIL)|%(?:\d+\$)?[dsu]/';
        // The same construct, plus the single space that would otherwise be orphaned
        // when the whole placeholder is removed rather than substituted.
        $strayPlaceholder = '/[ \t]?(?:%%(*SKIP)(*FAIL)|%(?:\d+\$)?[dsu])/';

        $parts = \preg_split($placeholder, $template, 2);
        if (false === $parts || 1 === \count($parts)) {
            // No recognisable placeholder: keep the text and put the counter first,
            // rather than dropping either of them.
            $parts = ['', '' === $template ? '' : ' ' . $template];
        }

        $clean = static function (string $text) use ($strayPlaceholder): string {
            // Any FURTHER placeholder belongs to no argument; drop it, then collapse
            // the escaped percents that survived the split.
            $text = (string) \preg_replace($strayPlaceholder, '', $text);

            return \str_replace('%%', '%', $text);
        };

        return [
            \htmlspecialchars($clean($parts[0]), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
            \htmlspecialchars($clean($parts[1] ?? ''), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
        ];
    }

    /**
     * Sticky selection counter + empty-guard messaging for bulk forms.
     * Optional $actionButtonsHtml repeats Select All controls so every tab has them at the bottom.
     */
    public static function renderStickyBar(string $actionButtonsHtml = ''): string
    {
        $countTpl = Lang::text('_AM_MODULEINSTALLER_SELECTED_COUNT', '%d selected');
        $noneMsg = Lang::text('_AM_MODULEINSTALLER_ERR_NONE_SELECTED', 'Select at least one module.');
        // <bdi>: the counter is a neutral numeral at a direction boundary, so in an
        // RTL admin '%d selected' rendered as "selected 1". The element is built here,
        // where it is a constant, rather than passed into the splitter.
        [$before, $after] = self::selectedCountParts($countTpl);
        $label = $before
            . '<bdi class="installer-selected-count-num" id="installer-selected-count">0</bdi>'
            . $after;

        $actions = '' !== $actionButtonsHtml
            ? '<span class="installer-sticky-actions">' . $actionButtonsHtml . '</span>'
            : '';

        // Extra blank line above count is handled in CSS (margin/border-top on .installer-sticky-bar)
        return '<div id="installer-sticky-bar" class="installer-sticky-bar">'
            . '<div class="installer-sticky-countline">'
            . '<strong class="installer-selected-label">' . $label . '</strong>'
            . '<span id="installer-empty-warn" class="installer-empty-warn installer-hidden">'
            . \htmlspecialchars($noneMsg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            . '</span></div>'
            . $actions
            . '</div>';
    }

    /**
     * Compact Yes/No radios for a module row.
     *
     * Nested mini-table keeps Yes/No adjacent on every row (including the first),
     * immune to theme flex/label rules that stretched radios across a wide cell.
     * IDs use a safe suffix (…_1 / …_2) so selectAll/unselectAll keep working.
     * Name keeps modules[dirname] for POST.
     */
    public static function renderYesNoRadios(string $dirname, int $value): string
    {
        $safeId = 'modsel_' . \preg_replace('/[^a-zA-Z0-9_-]/', '_', $dirname);
        $nameAttr = 'modules[' . \htmlspecialchars($dirname, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . ']';
        $idYes = $safeId . '_1';
        $idNo = $safeId . '_2';
        $onclick = "selectModule('" . \htmlspecialchars($dirname, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . "', this)";
        // Core constants from language/<lang>/global.php, so a different domain.
        $yesLabel = Lang::core('_YES', 'Yes');
        $noLabel = Lang::core('_NO', 'No');
        $yesChk = (1 === $value) ? ' checked' : '';
        $noChk = (1 === $value) ? '' : ' checked';

        // Single-line nowrap unit — first row cannot stretch Yes/No across the cell
        return '<span class="installer-yesno">'
            . '<label class="installer-yesno-opt" for="' . $idYes . '">'
            . '<input type="radio" name="' . $nameAttr . '" id="' . $idYes . '" value="1"'
            . $yesChk . ' onclick="' . $onclick . '">&nbsp;'
            . \htmlspecialchars($yesLabel, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            . '</label>&nbsp;&nbsp;'
            . '<label class="installer-yesno-opt" for="' . $idNo . '">'
            . '<input type="radio" name="' . $nameAttr . '" id="' . $idNo . '" value="0"'
            . $noChk . ' onclick="' . $onclick . '">&nbsp;'
            . \htmlspecialchars($noLabel, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            . '</label></span>';
    }

    /**
     * Run bulk action from POST and return report HTML.
     *
     * @return array{pageHasForm: bool, content: string, results: list<ModuleActionResult>}
     */
    public static function handlePost(string $action, string $successTitle): array
    {
        $service = new ModuleActionService();
        $selected = $service->selectedDirnames(self::postedModules());
        if ([] === $selected) {
            $msg = Lang::text('_AM_MODULEINSTALLER_ERR_NONE_SELECTED', 'Select at least one module (Yes) before continuing.');

            return [
                'pageHasForm' => false,
                'content' => "<div class='errorMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>',
                'results' => [],
            ];
        }
        $results = $service->runMany($action, $selected);
        $service->flushCaches();

        return [
            'pageHasForm' => false,
            'content' => self::renderReport($results, $successTitle),
            'results' => $results,
        ];
    }

    /**
     * HTML report for bulk / set-apply results (presentation layer).
     *
     * @param list<ModuleActionResult> $results
     */
    public static function renderReport(array $results, string $title = ''): string
    {
        if ([] === $results) {
            $msg = Lang::text('_AM_MODULEINSTALLER_NO_SELECTION_REPORT', 'No modules selected.');

            return "<div class='x2-note confirmMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
        }

        $html = '';
        if ('' !== $title) {
            $html .= "<div class='x2-note successMsg'>" . \htmlspecialchars($title, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
        }
        $catalog = new ModuleCatalog();
        $html .= "<ul class='log installer-result-log'>";
        foreach ($results as $result) {
            if (! $result instanceof ModuleActionResult) {
                continue;
            }
            $class = match ($result->status) {
                ModuleActionResult::STATUS_OK => 'success',
                ModuleActionResult::STATUS_SKIP => 'warning',
                default => 'error',
            };
            $prefix = \strtoupper($result->status);

            // loadInfo() executes the module's own xoops_version.php. By this point
            // the install/uninstall has already run, so a manifest that throws must
            // not replace a completed report with a blank page: degrade to dirname.
            $info = null;
            if (1 === \preg_match('/^[a-zA-Z0-9_-]+$/', $result->dirname)) {
                try {
                    $info = $catalog->loadInfo($result->dirname);
                } catch (\Throwable) {
                    $info = null;
                }
            }

            $logoUrl = self::moduleLogoUrl($result->dirname, $info);
            $name = null !== $info && '' !== \trim($info['name'])
                ? $info['name']
                : $result->dirname;

            $html .= '<li class="installer-result-item ' . $class . '">';

            // Logo first in DOM order so it is also first in the reading order, and
            // alt="" because the name is already in the label beside it: the image
            // is decorative, and repeating the name makes a screen reader say it twice.
            if (null !== $logoUrl) {
                $html .= '<img class="installer-result-logo" alt="" src="'
                    . \htmlspecialchars($logoUrl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '">';
            } else {
                $html .= '<span class="installer-result-logo"></span>';
            }

            $html .= '<div class="installer-result-body">'
                . '<div class="installer-result-label"><strong>['
                . \htmlspecialchars($prefix, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                . ']</strong> '
                . \htmlspecialchars($name, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                . ' <span class="installer-result-dirname">('
                . \htmlspecialchars($result->dirname, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                . ')</span></div>'
                // messageHtml() emits no attribute except its own class, so nothing
                // here needs a URL or attribute policy.
                . '<div class="installer-result-message">' . $result->messageHtml() . '</div>'
                . '</div></li>';
        }

        return $html . '</ul>';
    }

    /**
     * Build a same-origin URL for a module logo after proving it is a local file.
     *
     * Module manifests are executable third-party code, so their image value is
     * not inserted into an HTML attribute directly. Result reports call this
     * helper to keep the legacy logo while preventing traversal or URL injection.
     *
     * The dirname allowlist is deliberately NOT widened to accept dots. It is not a
     * logo rule — it is this module's dirname contract, applied identically by
     * ModuleCatalog::existsOnDisk() and ModuleSet::withDirnames(). A module whose
     * folder contains a dot is rejected by the catalog before a row is ever built,
     * so relaxing it here alone would suppress nothing that currently renders; it
     * would only make the one security-relevant path disagree with the other two.
     * Widening is a module-wide decision that needs core's agreement and its own
     * tests, not a local edit to the helper that happens to be reviewed.
     *
     * @param array{
     *     dirname:string,
     *     name:string,
     *     version:mixed,
     *     module_status:mixed,
     *     image:string,
     *     description:string
     * }|null $info
     */
    public static function moduleLogoUrl(string $dirname, ?array $info): ?string
    {
        $dirname = \trim($dirname);
        if (
            null === $info
            || 1 !== \preg_match('/^[a-zA-Z0-9_-]+$/', $dirname)
        ) {
            return null;
        }

        // 1 === preg_match(), not a bare truth test: preg_match() returns int|false,
        // and its false-on-error is falsy — so a bare call would let a control
        // character through on the one occasion the guard actually mattered. Matches
        // the form the dirname check above already uses.
        $image = \str_replace('\\', '/', \trim($info['image']));
        if (
            '' === $image
            || \str_starts_with($image, '/')
            || 1 === \preg_match('/[\x00-\x1F\x7F]/', $image)
        ) {
            return null;
        }

        $segments = \explode('/', $image);
        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return null;
            }
        }

        $moduleRoot = \realpath(\XOOPS_ROOT_PATH . '/modules/' . $dirname);
        if (false === $moduleRoot) {
            return null;
        }

        $logoPath = \realpath($moduleRoot . \DIRECTORY_SEPARATOR . \implode(\DIRECTORY_SEPARATOR, $segments));
        if (false === $logoPath || ! \is_file($logoPath)) {
            return null;
        }

        // Containment check, case-folded only where the filesystem is: on a
        // case-sensitive filesystem two genuinely different directories can differ
        // only in case, and folding both sides would accept one for the other.
        $rootPrefix = $moduleRoot . \DIRECTORY_SEPARATOR;
        $inside = '\\' === \DIRECTORY_SEPARATOR
            ? \str_starts_with(\mb_strtolower($logoPath), \mb_strtolower($rootPrefix))
            : \str_starts_with($logoPath, $rootPrefix);
        if (! $inside) {
            return null;
        }

        // Containment proves the target is inside the module, not that it is an image.
        // A manifest is third-party PHP and may name any contained file, and the value
        // becomes an <img src> the browser will GET — pointing it at a .php in the same
        // folder would execute that script server-side on every report render. An
        // extension allowlist is the cheap half of that; the module's own files are
        // web-reachable regardless, so this bounds what THIS page will ask for.
        $extension = \mb_strtolower(\pathinfo($logoPath, \PATHINFO_EXTENSION));
        if (! \in_array($extension, ['png', 'gif', 'jpg', 'jpeg', 'webp', 'svg', 'ico'], true)) {
            return null;
        }

        $encodedImage = \implode('/', \array_map(\rawurlencode(...), $segments));

        return \rtrim(\XOOPS_URL, '/') . '/modules/' . \rawurlencode($dirname) . '/' . $encodedImage;
    }

    /**
     * Select All / Un-Select All buttons plus Module Sets dropdown.
     *
     * Call after displayNavigation() on GET listing pages (not after a completed POST).
     */
    public static function displaySelectionControls(bool $showSetSelector = true): void
    {
        self::ensureAssets();
        $adminObject = Admin::getInstance();
        // Also raw constants until now, and reached from every listing page.
        $adminObject->addItemButton(Lang::text('_AM_MODULEINSTALLER_SELECT_ALL', 'Select All'), 'javascript:selectAll();', 'button_ok');
        $adminObject->addItemButton(Lang::text('_AM_MODULEINSTALLER_SELECT_NONE', 'Un-Select All'), 'javascript:unselectAll();', 'prune');
        $adminObject->displayButton('left', '');

        if ($showSetSelector) {
            echo self::renderSetSelector();
        }
    }

    /**
     * Dropdown of saved module sets; selecting one checks Yes for matching modules on this page.
     *
     * Self-contained: does not depend on xo-installer.js being loaded or non-stale.
     */
    public static function renderSetSelector(): string
    {
        $sets = [];

        try {
            $repo = new ModuleSetRepository();
            $repo->ensureStorage();
            $sets = $repo->listAll();
        } catch (\Throwable) {
            $sets = [];
        }

        $map = [];
        foreach ($sets as $set) {
            $map[$set->getId()] = $set->getModules();
        }

        // Keep JSON simple for data-attribute + script (no HEX_* rewrites that can confuse keys)
        $json = \json_encode($map, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            $json = '{}';
        }
        $jsonAttr = \htmlspecialchars($json, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        $label = Lang::text('_AM_MODULEINSTALLER_APPLY_SET', 'Apply set');
        $placeholder = Lang::text('_AM_MODULEINSTALLER_APPLY_SET_PLACEHOLDER', '— Select a set —');
        $emptyHint = Lang::text('_AM_MODULEINSTALLER_APPLY_SET_EMPTY', 'No sets yet — create one under Module Sets.');
        $hint = Lang::text('_AM_MODULEINSTALLER_APPLY_SET_HINT', 'Checks Yes for set members listed on this page.');

        $lastSetId = isset($_COOKIE['installer_last_set'])
            ? (string) \preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $_COOKIE['installer_last_set'])
            : '';

        $html = '<div id="installer-set-toolbar" class="installer-set-toolbar clearfix">';
        $html .= '<label for="installer-set-select" class="installer-set-toolbar-label" title="'
            . \htmlspecialchars($hint, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '">'
            . \htmlspecialchars($label, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</label> ';
        $html .= '<select id="installer-set-select" name="installer_set_select" class="form-control installer-set-select" '
            . 'data-sets="' . $jsonAttr . '" title="'
            . \htmlspecialchars($hint, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '">';
        $html .= '<option value="">' . \htmlspecialchars($placeholder, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</option>';

        if ([] === $sets) {
            $html .= '<option value="" disabled>' . \htmlspecialchars($emptyHint, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</option>';
        } else {
            foreach ($sets as $set) {
                $count = $set->countModules();
                $optLabel = $set->getName() . ' (' . $count . ')';
                $sel = ($lastSetId !== '' && $lastSetId === $set->getId()) ? ' selected' : '';
                $html .= '<option value="' . \htmlspecialchars($set->getId(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '"' . $sel . '>'
                    . \htmlspecialchars($optLabel, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</option>';
            }
        }

        $html .= '</select>';
        $html .= ' <span class="small installer-set-toolbar-hint">'
            . \htmlspecialchars($hint, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        $html .= '</div>';

        // Self-contained handler: works even if xo-installer.js is missing or cached stale.
        // Matches modules by <tr id="dirname"> which AdminBulkPage always emits.
        $html .= <<<'JS_WRAP'
        <script type="text/javascript">
        (function () {
            function installerSelectSet(moduleList) {
                var wanted = {};
                if (moduleList && moduleList.length) {
                    for (var i = 0; i < moduleList.length; i++) {
                        wanted[String(moduleList[i])] = true;
                    }
                }
                var matched = 0;
                var rows = document.querySelectorAll('table.module tr[id]');
                if (!rows.length) {
                    rows = document.querySelectorAll('tr[id]');
                }
                for (var r = 0; r < rows.length; r++) {
                    var row = rows[r];
                    var dirname = row.id;
                    if (!dirname) {
                        continue;
                    }
                    var wantYes = !!wanted[dirname];
                    var radios = row.querySelectorAll('input[type="radio"]');
                    if (!radios.length) {
                        continue;
                    }
                    var yesRadio = null;
                    var noRadio = null;
                    for (var j = 0; j < radios.length; j++) {
                        if (String(radios[j].value) === '1') {
                            yesRadio = radios[j];
                        } else if (String(radios[j].value) === '0') {
                            noRadio = radios[j];
                        }
                    }
                    if (wantYes) {
                        if (yesRadio) {
                            yesRadio.checked = true;
                        }
                        row.style.background = '#E6EFC2';
                        matched++;
                    } else {
                        if (noRadio) {
                            noRadio.checked = true;
                        }
                        row.style.background = 'transparent';
                    }
                }
                return matched;
            }

            function installerApplySet(selectEl) {
                if (!selectEl) {
                    return 0;
                }
                var setId = selectEl.value;
                if (!setId) {
                    return 0;
                }
                var map = window.installerModuleSets || {};
                if ((!map || !Object.keys(map).length) && selectEl.getAttribute('data-sets')) {
                    try {
                        map = JSON.parse(selectEl.getAttribute('data-sets'));
                    } catch (e) {
                        map = {};
                    }
                }
                window.installerModuleSets = map;
                var modules = map[setId] || [];
                return installerSelectSet(modules);
            }

            // Expose globally for toolbar + any external callers
            window.installerSelectSet = installerSelectSet;
            window.applyInstallerSet = installerApplySet;
            window.selectSet = window.selectSet || installerSelectSet;

            window.installerModuleSets =
        JS_WRAP;
        $html .= $json . ";\n";

        return $html . <<<'JS'
    function bindSetSelect() {
        var sel = document.getElementById('installer-set-select');
        if (!sel || sel.getAttribute('data-bound') === '1') {
            return;
        }
        sel.setAttribute('data-bound', '1');
        sel.addEventListener('change', function () {
            installerApplySet(sel);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindSetSelect);
    } else {
        bindSetSelect();
    }
    setTimeout(bindSetSelect, 0);
})();
</script>
JS;
    }
}
