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
    /** Cache-bust query for admin CSS/JS (bump when assets change). */
    public const ASSET_VERSION = '1709';

    /**
     * Full bulk page after xoops_cp_header() — navigation, list or report, form, assets.
     *
     * @param string $action One of ModuleActionService::ACTION_*
     */
    public static function serve(string $action): void
    {
        self::ensureAssets();
        $action    = \mb_strtolower(\trim($action));
        $navScript = \basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'install.php'));

        $adminObject = Admin::getInstance();
        $adminObject->displayNavigation($navScript);

        $pageHasForm = true;
        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            if (!self::checkCsrf()) {
                $pageHasForm = false;
                $msg         = \defined('_AM_MODULEINSTALLER_ERR_TOKEN')
                    ? \_AM_MODULEINSTALLER_ERR_TOKEN
                    : 'Invalid security token. Please try again.';
                $content     = "<div class='errorMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
            } else {
                $handled     = self::handlePost($action, self::successTitleFor($action));
                $pageHasForm = $handled['pageHasForm'];
                $content     = $handled['content'];
            }
        } else {
            $built       = self::buildListPage($action);
            $content     = $built['content'];
            $pageHasForm = $built['pageHasForm'];
        }

        if ($pageHasForm) {
            self::displaySelectionControls(true);
            echo self::wrapBulkForm($content, $navScript);
        } else {
            echo $content;
            echo '<p class="installer-back-link"><a class="formButton" href="index.php">'
                . \htmlspecialchars(
                    \defined('_AM_MODULEINSTALLER_BACK_HOME') ? \_AM_MODULEINSTALLER_BACK_HOME : 'Installer home',
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
        if (!isset($GLOBALS['xoTheme']) || !\is_object($GLOBALS['xoTheme'])) {
            return;
        }
        $dirname = 'moduleinstaller';
        if (isset($GLOBALS['xoopsModule']) && \is_object($GLOBALS['xoopsModule'])) {
            $dirname = (string) $GLOBALS['xoopsModule']->getVar('dirname');
        }
        $base = \XOOPS_URL . '/modules/' . $dirname . '/assets';
        $v    = self::ASSET_VERSION;
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
        $action  = \mb_strtolower(\trim($action));

        if (ModuleActionService::ACTION_UPDATE === $action) {
            return self::buildUpdateListPage($catalog);
        }

        $list = $catalog->candidatesFor($action);
        if ([] === $list) {
            return [
                'content'     => self::emptyListMessage(),
                'pageHasForm' => false,
            ];
        }

        return [
            'content'     => self::renderModuleTable($list),
            'pageHasForm' => true,
        ];
    }

    /**
     * @return array{content: string, pageHasForm: bool}
     */
    private static function buildUpdateListPage(ModuleCatalog $catalog): array
    {
        $list      = $catalog->candidatesFor(ModuleActionService::ACTION_UPDATE);
        $needs     = $catalog->listNeedsUpdate();
        $onlyNeeds = Request::getInt('needs_only', 0, 'GET') === 1;
        if ($onlyNeeds) {
            $list = $needs;
        }

        if ([] === $list) {
            return [
                'content'     => self::emptyListMessage(),
                'pageHasForm' => false,
            ];
        }

        $content = '';
        if ($onlyNeeds) {
            $content .= "<div class='x2-note confirmMsg'>" . \_AM_MODULEINSTALLER_PRESELECTED_UPDATES . '</div>';
        }
        $toggleUrl = 'update.php' . ($onlyNeeds ? '' : '?needs_only=1');
        $toggleLbl = \_AM_MODULEINSTALLER_SHOW_UPDATES_ONLY;
        $content  .= '<p><a class="formButton" href="' . \htmlspecialchars($toggleUrl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '">'
            . \htmlspecialchars($toggleLbl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            . ($onlyNeeds ? ' ✓' : '') . '</a></p>';
        $content  .= self::renderModuleTable($list, $needs, static function (string $dirname, array $info) use ($catalog): array {
            return ['highlight' => $catalog->needsUpdate($dirname)];
        });

        return [
            'content'     => $content,
            'pageHasForm' => true,
        ];
    }

    private static function emptyListMessage(): string
    {
        $msg = \defined('_AM_MODULEINSTALLER_NO_MODULES')
            ? \_AM_MODULEINSTALLER_NO_MODULES
            : 'No modules found.';

        return "<div class='x2-note confirmMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    }

    private static function successTitleFor(string $action): string
    {
        $map = [
            ModuleActionService::ACTION_INSTALL    => '_AM_MODULEINSTALLER_DONE_INSTALL',
            ModuleActionService::ACTION_UNINSTALL  => '_AM_MODULEINSTALLER_DONE_UNINSTALL',
            ModuleActionService::ACTION_ACTIVATE   => '_AM_MODULEINSTALLER_DONE_ACTIVATE',
            ModuleActionService::ACTION_DEACTIVATE => '_AM_MODULEINSTALLER_DONE_DEACTIVATE',
            ModuleActionService::ACTION_UPDATE     => '_AM_MODULEINSTALLER_DONE_UPDATE',
        ];
        $const = $map[$action] ?? '';
        if ('' !== $const && \defined($const)) {
            return (string) \constant($const);
        }

        return \ucfirst($action) . ' complete';
    }

    private static function checkCsrf(): bool
    {
        if (!isset($GLOBALS['xoopsSecurity']) || !\is_object($GLOBALS['xoopsSecurity'])) {
            return true;
        }

        return (bool) $GLOBALS['xoopsSecurity']->check();
    }

    private static function wrapBulkForm(string $innerHtml, string $actionScript): string
    {
        $actionEsc = \htmlspecialchars($actionScript, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        $submitLbl = \htmlspecialchars(
            \defined('_AM_MODULEINSTALLER_SUBMIT') ? \_AM_MODULEINSTALLER_SUBMIT : 'Continue',
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
        $catalog   = new ModuleCatalog();
        $preselect = \array_fill_keys($preselect, true);
        $filterPh  = \defined('_AM_MODULEINSTALLER_FILTER_PLACEHOLDER') ? \_AM_MODULEINSTALLER_FILTER_PLACEHOLDER : 'Filter…';
        $filterLbl = \defined('_AM_MODULEINSTALLER_FILTER') ? \_AM_MODULEINSTALLER_FILTER : 'Filter';
        $filterHint = \defined('_AM_MODULEINSTALLER_FILTER_HINT') ? \_AM_MODULEINSTALLER_FILTER_HINT : '';
        $noneMsg   = \defined('_AM_MODULEINSTALLER_SET_FILTER_NONE') ? \_AM_MODULEINSTALLER_SET_FILTER_NONE : 'No match';

        $selectAllLbl = \htmlspecialchars(
            \defined('_AM_MODULEINSTALLER_SELECT_ALL') ? \_AM_MODULEINSTALLER_SELECT_ALL : 'Select All',
            \ENT_QUOTES | \ENT_SUBSTITUTE,
            'UTF-8'
        );
        $selectNoneLbl = \htmlspecialchars(
            \defined('_AM_MODULEINSTALLER_SELECT_NONE') ? \_AM_MODULEINSTALLER_SELECT_NONE : 'Un-Select All',
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
        $content .= '<p id="installer-filter-empty" class="x2-note confirmMsg" style="display:none;">'
            . \htmlspecialchars($noneMsg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        // No ul/li wrapper: list markers broke first-row radio layout (black square + stretched Yes/No)
        $content .= "<div class='installer-module-list'>"
            . "<table class='outer module installer-module-table width100'>\n"
            . "<colgroup><col class='installer-col-img'><col class='installer-col-desc'><col class='installer-col-yesno'></colgroup>\n";
        $count   = 0;
        $even    = false;

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
                if (\is_array($meta) && !empty($meta['highlight'])) {
                    $highlight = true;
                }
            }

            $spanOpen  = $highlight ? "<span style='color:#FF0000;font-weight:bold;'>" : '<span>';
            $spanClose = '</span>';

            $imgSrc     = \XOOPS_URL . '/modules/' . $info['dirname'] . '/' . $info['image'];
            $dirnameEsc = \htmlspecialchars($file, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
            $nameEsc    = \htmlspecialchars($info['name'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
            $toggleJs   = "toggleModuleRow('" . $dirnameEsc . "')";
            $searchBlob = \htmlspecialchars(
                \mb_strtolower($info['name'] . ' ' . $file . ' ' . $info['description']),
                \ENT_QUOTES | \ENT_SUBSTITUTE,
                'UTF-8'
            );
            $even     = !$even;
            $stripe   = $even ? 'even' : 'odd';
            $rowClass = \trim($stripe . ($value ? ' installer-row-selected' : ''));
            $content .= "<tr id='" . $dirnameEsc . "' class='" . $rowClass . "' data-search=\"" . $searchBlob . "\""
                . ($value ? " style='background-color:var(--installer-selected,#E6EFC2);'" : '') . ">\n";
            $content .= "    <td class='img installer-mod-toggle' onclick=\"" . $toggleJs . "\" title='Toggle selection'>";
            $content .= "<img class='installer-mod-logo' src='" . \htmlspecialchars($imgSrc, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                . "' alt='" . $nameEsc . "'></td>\n";
            $content .= "    <td class='installer-mod-toggle installer-mod-desc' onclick=\"" . $toggleJs . "\" title='Toggle selection'>" . $spanOpen;
            $content .= '        ' . $nameEsc
                . '&nbsp;' . \htmlspecialchars((string) $info['version'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                . '&nbsp;' . \htmlspecialchars((string) $info['module_status'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
                . '&nbsp;(folder: /' . \htmlspecialchars($info['dirname'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . ')';
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
     * Sticky selection counter + empty-guard messaging for bulk forms.
     * Optional $actionButtonsHtml repeats Select All controls so every tab has them at the bottom.
     */
    public static function renderStickyBar(string $actionButtonsHtml = ''): string
    {
        $countTpl = \defined('_AM_MODULEINSTALLER_SELECTED_COUNT') ? \_AM_MODULEINSTALLER_SELECTED_COUNT : '%d selected';
        $noneMsg  = \defined('_AM_MODULEINSTALLER_ERR_NONE_SELECTED') ? \_AM_MODULEINSTALLER_ERR_NONE_SELECTED : 'Select at least one module.';
        $parts    = \explode('%d', $countTpl, 2);
        $label    = \htmlspecialchars($parts[0] ?? '', \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            . '<span class="installer-selected-count-num" id="installer-selected-count">0</span>'
            . \htmlspecialchars($parts[1] ?? '', \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        $actions = '' !== $actionButtonsHtml
            ? '<span class="installer-sticky-actions">' . $actionButtonsHtml . '</span>'
            : '';

        // Extra blank line above count is handled in CSS (margin/border-top on .installer-sticky-bar)
        return '<div id="installer-sticky-bar" class="installer-sticky-bar">'
            . '<div class="installer-sticky-countline">'
            . '<strong class="installer-selected-label">' . $label . '</strong>'
            . '<span id="installer-empty-warn" class="installer-empty-warn" style="display:none;">'
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
        $safeId   = 'modsel_' . \preg_replace('/[^a-zA-Z0-9_-]/', '_', $dirname);
        $nameAttr = 'modules[' . \htmlspecialchars($dirname, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . ']';
        $idYes    = $safeId . '_1';
        $idNo     = $safeId . '_2';
        $onclick  = "selectModule('" . \htmlspecialchars($dirname, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . "', this)";
        $yesLabel = \defined('_YES') ? \_YES : 'Yes';
        $noLabel  = \defined('_NO') ? \_NO : 'No';
        $yesChk   = (1 === $value) ? ' checked' : '';
        $noChk    = (1 === $value) ? '' : ' checked';

        // Single-line nowrap unit — first row cannot stretch Yes/No across the cell
        return '<span class="installer-yesno" style="display:inline-block;white-space:nowrap;">'
            . '<label class="installer-yesno-opt" for="' . $idYes . '">'
            . '<input type="radio" name="' . $nameAttr . '" id="' . $idYes . '" value="1"'
            . $yesChk . " onclick=\"" . $onclick . '">&nbsp;'
            . \htmlspecialchars($yesLabel, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            . '</label>&nbsp;&nbsp;'
            . '<label class="installer-yesno-opt" for="' . $idNo . '">'
            . '<input type="radio" name="' . $nameAttr . '" id="' . $idNo . '" value="0"'
            . $noChk . " onclick=\"" . $onclick . '">&nbsp;'
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
        $service  = new ModuleActionService();
        $selected = $service->selectedDirnames(self::postedModules());
        if ([] === $selected) {
            $msg = \defined('_AM_MODULEINSTALLER_ERR_NONE_SELECTED')
                ? \_AM_MODULEINSTALLER_ERR_NONE_SELECTED
                : 'Select at least one module (Yes) before continuing.';

            return [
                'pageHasForm' => false,
                'content'     => "<div class='errorMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>',
                'results'     => [],
            ];
        }
        $results = $service->runMany($action, $selected);
        $service->flushCaches();

        return [
            'pageHasForm' => false,
            'content'     => self::renderReport($results, $successTitle),
            'results'     => $results,
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
            $msg = \defined('_AM_MODULEINSTALLER_NO_SELECTION_REPORT')
                ? \_AM_MODULEINSTALLER_NO_SELECTION_REPORT
                : 'No modules selected.';

            return "<div class='x2-note confirmMsg'>" . \htmlspecialchars($msg, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
        }

        $html = '';
        if ('' !== $title) {
            $html .= "<div class='x2-note successMsg'>" . \htmlspecialchars($title, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '</div>';
        }
        $html .= "<ul class='log installer-result-log'>";
        foreach ($results as $result) {
            if (!$result instanceof ModuleActionResult) {
                continue;
            }
            $class = match ($result->status) {
                ModuleActionResult::STATUS_OK   => 'success',
                ModuleActionResult::STATUS_SKIP => 'warning',
                default                         => 'error',
            };
            $prefix = \strtoupper($result->status);
            // Core modulesadmin may return HTML in $message — pass through as historically done
            $html .= '<li class="' . $class . '"><strong>[' . \htmlspecialchars($prefix, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . ']</strong> '
                . \htmlspecialchars($result->dirname, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . ': '
                . $result->message . '</li>';
        }
        $html .= '</ul>';

        return $html;
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
        $adminObject->addItemButton(_AM_MODULEINSTALLER_SELECT_ALL, 'javascript:selectAll();', 'button_ok');
        $adminObject->addItemButton(_AM_MODULEINSTALLER_SELECT_NONE, 'javascript:unselectAll();', 'prune');
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

        $label = \defined('_AM_MODULEINSTALLER_APPLY_SET') ? \_AM_MODULEINSTALLER_APPLY_SET : 'Apply set';
        $placeholder = \defined('_AM_MODULEINSTALLER_APPLY_SET_PLACEHOLDER')
            ? \_AM_MODULEINSTALLER_APPLY_SET_PLACEHOLDER
            : '— Select a set —';
        $emptyHint = \defined('_AM_MODULEINSTALLER_APPLY_SET_EMPTY')
            ? \_AM_MODULEINSTALLER_APPLY_SET_EMPTY
            : 'No sets yet — create one under Module Sets.';
        $hint = \defined('_AM_MODULEINSTALLER_APPLY_SET_HINT')
            ? \_AM_MODULEINSTALLER_APPLY_SET_HINT
            : 'Checks Yes for set members listed on this page.';

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
        $html .= <<<'JS'
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
JS;
        $html .= $json . ";\n";
        $html .= <<<'JS'
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

        return $html;
    }
}

