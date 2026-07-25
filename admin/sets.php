<?php declare(strict_types=1);

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
 * Module sets admin — CRUD for named collections of modules (testing focus).
 */

use Xmf\Module\Admin;
use Xmf\Request;
use XoopsModules\Moduleinstaller\Helper;
use XoopsModules\Moduleinstaller\ModuleCatalog;
use XoopsModules\Moduleinstaller\Set\ModuleSet;
use XoopsModules\Moduleinstaller\Set\ModuleSetApplier;
use XoopsModules\Moduleinstaller\Set\ModuleSetRepository;
use XoopsModules\Moduleinstaller\Set\ModuleSetResolver;

require_once __DIR__ . '/admin_header.php';

$helper = Helper::getInstance();
$helper->loadLanguage('admin');
$helper->loadLanguage('modinfo');

$repository = new ModuleSetRepository();
$catalog = new ModuleCatalog();
$resolver = new ModuleSetResolver($catalog);
$applier = new ModuleSetApplier(null, $resolver, $repository);

$op = Request::getCmd('op', 'list');
$id = Request::getString('id', '');

/**
 * Redirect helper with message.
 */
$redirect = static function (string $msg, string $type = 's'): void {
    redirect_header('sets.php', 2, $msg);
};

// --- Mutations (POST) ---
if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    if (! $GLOBALS['xoopsSecurity']->check()) {
        redirect_header('sets.php', 3, implode('<br>', $GLOBALS['xoopsSecurity']->getErrors()));
    }

    try {
        $repository->ensureStorage();

        switch ($op) {
            case 'save':
                $name = Request::getString('name', '', 'POST');
                $description = Request::getString('description', '', 'POST');
                $setId = ModuleSet::normalizeId(Request::getString('id', '', 'POST'));
                $modulesMap = Request::getArray('modules', [], 'POST');
                $selected = [];
                foreach ($modulesMap as $dirname => $flag) {
                    if ((int) $flag === 1 || '1' === $flag) {
                        $selected[] = (string) $dirname;
                    }
                }
                // Also accept multi-select style modules_list[]
                $list = Request::getArray('modules_list', [], 'POST');
                if (\is_array($list) && [] !== $list) {
                    foreach ($list as $dirname) {
                        $selected[] = (string) $dirname;
                    }
                }

                if ('' === \trim($name)) {
                    throw new InvalidArgumentException(_AM_MODULEINSTALLER_SET_ERR_NAME);
                }

                if ('' === $setId) {
                    $setId = $repository->uniqueId(ModuleSet::idFromName($name));
                    $set = new ModuleSet($setId, $name, $description, $selected);
                } else {
                    $existing = $repository->get($setId);
                    if (! $existing instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
                        $set = new ModuleSet($setId, $name, $description, $selected);
                    } else {
                        $set = $existing->withName($name)->withDescription($description)->withModules($selected);
                    }
                }
                $repository->save($set);
                $redirect(\sprintf(_AM_MODULEINSTALLER_SET_SAVED, $set->getName()));

                break;

            case 'delete':
                $setId = ModuleSet::normalizeId(Request::getString('id', '', 'POST'));
                if ('' === $setId || ! $repository->delete($setId)) {
                    throw new RuntimeException(_AM_MODULEINSTALLER_SET_ERR_NOTFOUND);
                }
                $redirect(_AM_MODULEINSTALLER_SET_DELETED);

                break;

            case 'duplicate':
                $setId = ModuleSet::normalizeId(Request::getString('id', '', 'POST'));
                $newName = Request::getString('name', '', 'POST');
                $copy = $repository->duplicate($setId, $newName);
                $redirect(\sprintf(_AM_MODULEINSTALLER_SET_DUPLICATED, $copy->getName()));

                break;

            case 'create_from_active':
                $name = Request::getString('name', '', 'POST');
                if ('' === \trim($name)) {
                    $name = _AM_MODULEINSTALLER_SET_FROM_ACTIVE_DEFAULT;
                }
                $active = $catalog->listInstalled(true);
                $set = $repository->createFromModules($name, $active, _AM_MODULEINSTALLER_SET_FROM_ACTIVE_DESC);
                $redirect(\sprintf(_AM_MODULEINSTALLER_SET_SAVED, $set->getName()));

                break;

            case 'import':
                $tmpName = (string) ($_FILES['import_file']['tmp_name'] ?? '');
                if ('' === $tmpName || ! \is_uploaded_file($tmpName)) {
                    throw new RuntimeException(_AM_MODULEINSTALLER_SET_ERR_IMPORT);
                }
                $raw = (string) \file_get_contents($tmpName);
                $set = installer_import_set_from_yaml($repository, $raw);
                $redirect(\sprintf(_AM_MODULEINSTALLER_SET_IMPORTED, $set->getName()));

                break;

            case 'apply':
                $setId = ModuleSet::normalizeId(Request::getString('id', '', 'POST'));
                $action = Request::getCmd('action', '', 'POST');
                $set = $repository->get($setId);
                if (! $set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
                    throw new RuntimeException(_AM_MODULEINSTALLER_SET_ERR_NOTFOUND);
                }
                if (! \in_array($action, ModuleSetApplier::supportedActions(), true)) {
                    throw new InvalidArgumentException(_AM_MODULEINSTALLER_SET_ERR_ACTION);
                }
                // Destructive confirm
                if (\in_array($action, [ModuleSetApplier::ACTION_UNINSTALL, ModuleSetApplier::ACTION_FOCUS], true)) {
                    $confirm = Request::getInt('confirm', 0, 'POST');
                    if (1 !== $confirm) {
                        throw new RuntimeException(_AM_MODULEINSTALLER_SET_ERR_CONFIRM);
                    }
                }

                $outcome = $applier->execute($set, $action, true);
                if (null !== $outcome['snapshot_id'] && '' !== $outcome['snapshot_id']) {
                    $_SESSION['installer_last_snapshot_id'] = $outcome['snapshot_id'];
                }
                // Fall through to result display (no redirect)
                xoops_cp_header();
                \XoopsModules\Moduleinstaller\AdminBulkPage::ensureAssets();
                $adminObject = Admin::getInstance();
                $adminObject->displayNavigation(basename(__FILE__));
                $adminObject->addItemButton(_AM_MODULEINSTALLER_SET_BACK_LIST, 'sets.php', 'list');
                $adminObject->displayButton('left');

                echo '<h3>' . \sprintf(_AM_MODULEINSTALLER_SET_APPLY_DONE, \htmlspecialchars($set->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), \htmlspecialchars($action, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</h3>';

                if ([] !== $outcome['notices']) {
                    echo "<div class='x2-note confirmMsg'><strong>" . _AM_MODULEINSTALLER_SET_NOTICES . '</strong><ul>';
                    foreach ($outcome['notices'] as $notice) {
                        echo '<li>' . \htmlspecialchars((string) $notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
                    }
                    echo '</ul></div>';
                }
                if (null !== $outcome['snapshot_id'] && '' !== $outcome['snapshot_id']) {
                    $snapId = $outcome['snapshot_id'];
                    echo "<div class='x2-note successMsg'>" . \sprintf(
                        _AM_MODULEINSTALLER_SET_SNAPSHOT_SAVED,
                        \htmlspecialchars($snapId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    );
                    echo ' <a href="sets.php?op=apply_form&amp;id=' . \rawurlencode($snapId) . '">' . _AM_MODULEINSTALLER_SET_OPEN_SNAPSHOT . '</a>';
                    echo ' · <a class="formButton" href="sets.php?op=apply_form&amp;id=' . \rawurlencode($snapId) . '&amp;action=focus"><strong>'
                        . _AM_MODULEINSTALLER_SET_RESTORE_SNAPSHOT . '</strong></a>';
                    echo '</div>';
                }

                echo \XoopsModules\Moduleinstaller\AdminBulkPage::renderReport($outcome['results']);

                require_once __DIR__ . '/admin_footer.php';
                exit;

            default:
                $redirect(_AM_MODULEINSTALLER_SET_ERR_ACTION);
        }
    } catch (Throwable $e) {
        redirect_header('sets.php', 3, $e->getMessage());
    }
}

// --- Views (GET) ---

// Export streams a YAML file download with its own Content-Type/Disposition headers,
// so it must run BEFORE any admin chrome (xoops_cp_header) is emitted or headers_sent()
// would already be true and the YAML would be appended to the admin page. It always exits.
if ('export' === $op) {
    installer_export_set($repository, $id);
}

xoops_cp_header();
\XoopsModules\Moduleinstaller\AdminBulkPage::ensureAssets();
$adminObject = Admin::getInstance();
$adminObject->displayNavigation(basename(__FILE__));

try {
    $repository->ensureStorage();
} catch (Throwable $e) {
    echo "<div class='errorMsg'>" . \htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    require_once __DIR__ . '/admin_footer.php';
    exit;
}

match ($op) {
    'edit', 'new' => renderSetEditForm($repository, $catalog, $resolver, $id, 'new' === $op),
    'apply_form' => renderApplyForm($repository, $resolver, $applier, $id),
    'delete_confirm' => renderDeleteConfirm($repository, $id),
    'duplicate_form' => renderDuplicateForm($repository, $id),
    // 'export' is handled before xoops_cp_header() above (streams a file, then exits).
    default => renderSetList($repository, $resolver),
};

require_once __DIR__ . '/admin_footer.php';

/**
 * List all sets.
 */
function renderSetList(ModuleSetRepository $repository, ModuleSetResolver $resolver): void
{
    $adminObject = Admin::getInstance();
    $adminObject->addItemButton(_AM_MODULEINSTALLER_SET_NEW, 'sets.php?op=new', 'add');
    $adminObject->addItemButton(_AM_MODULEINSTALLER_SET_FROM_ACTIVE, 'javascript:document.getElementById("form-from-active").submit();', 'button_ok');
    $adminObject->displayButton('left');

    echo '<form id="form-from-active" method="post" action="sets.php" style="display:none;">';
    echo $GLOBALS['xoopsSecurity']->getTokenHTML();
    echo '<input type="hidden" name="op" value="create_from_active">';
    echo '<input type="hidden" name="name" value="' . \htmlspecialchars(_AM_MODULEINSTALLER_SET_FROM_ACTIVE_DEFAULT, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    echo '</form>';

    // Soft blue panel (same language as bulk "Apply set" bar)
    echo '<div class="installer-sets-panel">';
    echo '<p>' . _AM_MODULEINSTALLER_SET_INTRO . '</p>';
    echo '<p class="small"><code>' . \htmlspecialchars($repository->getStoragePath(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></p>';

    // Import YAML
    echo '<form method="post" action="sets.php" enctype="multipart/form-data" class="installer-list-toolbar">';
    echo $GLOBALS['xoopsSecurity']->getTokenHTML();
    echo '<input type="hidden" name="op" value="import">';
    echo '<label for="import_file"><strong>' . _AM_MODULEINSTALLER_SET_IMPORT . '</strong></label> ';
    echo '<input type="file" name="import_file" id="import_file" accept=".yml,.yaml,text/yaml,application/x-yaml"> ';
    echo '<button type="submit" class="formButton">' . _AM_MODULEINSTALLER_SET_IMPORT_BTN . '</button>';
    echo '</form>';
    echo '</div>';

    $sets = $repository->listAll();
    if ([] === $sets) {
        echo "<div class='x2-note confirmMsg'>" . _AM_MODULEINSTALLER_SET_EMPTY . '</div>';

        return;
    }

    echo '<table class="outer width100" cellspacing="1">';
    echo '<thead><tr>';
    echo '<th>' . _AM_MODULEINSTALLER_SET_NAME . '</th>';
    echo '<th>' . _AM_MODULEINSTALLER_SET_MODULES . '</th>';
    echo '<th>' . _AM_MODULEINSTALLER_SET_MISSING . '</th>';
    echo '<th>' . _AM_MODULEINSTALLER_SET_ACTIONS . '</th>';
    echo '</tr></thead><tbody>';

    $even = false;
    foreach ($sets as $set) {
        $even = ! $even;
        $class = $even ? 'even' : 'odd';
        $missing = $resolver->countMissing($set);
        $idEsc = \htmlspecialchars($set->getId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $nameEsc = \htmlspecialchars($set->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo '<tr class="' . $class . '">';
        echo '<td><strong>' . $nameEsc . '</strong><br><span class="small">' . $idEsc . '</span>';
        if ('' !== $set->getDescription()) {
            echo '<br>' . \htmlspecialchars($set->getDescription(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        echo '</td>';
        echo '<td class="center">' . $set->countModules() . '</td>';
        echo '<td class="center">' . ($missing > 0 ? '<span style="color:#c00;font-weight:bold;">' . $missing . '</span>' : '0') . '</td>';
        echo '<td class="center">';
        echo '<a href="sets.php?op=apply_form&amp;id=' . \rawurlencode($set->getId()) . '" title="' . \htmlspecialchars(_AM_MODULEINSTALLER_SET_TIP_APPLY, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . _AM_MODULEINSTALLER_SET_APPLY . '</a> | ';
        echo '<a href="sets.php?op=edit&amp;id=' . \rawurlencode($set->getId()) . '">' . _EDIT . '</a> | ';
        echo '<a href="sets.php?op=export&amp;id=' . \rawurlencode($set->getId()) . '">' . _AM_MODULEINSTALLER_SET_EXPORT . '</a> | ';
        echo '<a href="sets.php?op=duplicate_form&amp;id=' . \rawurlencode($set->getId()) . '">' . _AM_MODULEINSTALLER_SET_DUPLICATE . '</a> | ';
        echo '<a href="sets.php?op=delete_confirm&amp;id=' . \rawurlencode($set->getId()) . '">' . _DELETE . '</a>';
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

/**
 * Create / edit form.
 */
function renderSetEditForm(
    ModuleSetRepository $repository,
    ModuleCatalog $catalog,
    ModuleSetResolver $resolver,
    string $id,
    bool $isNew
): void {
    $adminObject = Admin::getInstance();
    $adminObject->addItemButton(_AM_MODULEINSTALLER_SET_BACK_LIST, 'sets.php', 'list');
    $adminObject->displayButton('left');

    $set = null;
    if (! $isNew && '' !== $id) {
        $set = $repository->get($id);
        if (! $set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
            echo "<div class='errorMsg'>" . _AM_MODULEINSTALLER_SET_ERR_NOTFOUND . '</div>';

            return;
        }
    }

    $name = $set?->getName() ?? '';
    $description = $set?->getDescription() ?? '';
    $setId = $set?->getId() ?? '';
    $selected = $set?->getModules() ?? [];
    $selectedMap = \array_fill_keys($selected, true);

    // Stale members still shown
    $resolvedMissing = [];
    if ($set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
        foreach ($resolver->resolve($set) as $dirname => $row) {
            if (ModuleSetResolver::STATE_MISSING === $row['state']) {
                $resolvedMissing[$dirname] = $row;
            }
        }
    }

    echo '<h3 class="installer-set-edit-title">' . ($isNew ? _AM_MODULEINSTALLER_SET_NEW : _AM_MODULEINSTALLER_SET_EDIT) . '</h3>';

    if ([] !== $resolvedMissing) {
        echo "<div class='x2-note confirmMsg'><strong>" . _AM_MODULEINSTALLER_SET_MISSING_NOTICE . '</strong><ul>';
        foreach ($resolvedMissing as $dirname => $row) {
            echo '<li><code>' . \htmlspecialchars($dirname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code> — '
                . \htmlspecialchars((string) $row['notice'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
        echo '</ul><p>' . _AM_MODULEINSTALLER_SET_MISSING_HINT . '</p></div>';
    }

    echo '<form method="post" action="sets.php" class="installer-set-edit-form">';
    echo $GLOBALS['xoopsSecurity']->getTokenHTML();
    echo '<input type="hidden" name="op" value="save">';
    if ('' !== $setId) {
        echo '<input type="hidden" name="id" value="' . \htmlspecialchars($setId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }

    // Details panel
    echo '<div class="installer-sets-panel installer-set-edit-meta">';
    echo '<div class="installer-set-field">';
    echo '<label for="installer-set-name"><strong>' . _AM_MODULEINSTALLER_SET_NAME . '</strong></label>';
    echo '<input type="text" id="installer-set-name" name="name" class="form-control installer-set-input" maxlength="120" value="'
        . \htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" required>';
    echo '</div>';
    echo '<div class="installer-set-field">';
    echo '<label for="installer-set-desc"><strong>' . _AM_MODULEINSTALLER_SET_DESC . '</strong></label>';
    echo '<input type="text" id="installer-set-desc" name="description" class="form-control installer-set-input" maxlength="255" value="'
        . \htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    echo '</div>';
    echo '</div>';

    // Modules toolbar (blue panel)
    echo '<h4 class="installer-set-modules-heading">' . _AM_MODULEINSTALLER_SET_MODULES . '</h4>';
    echo '<div class="installer-sets-panel installer-set-edit-tools">';
    echo '<div class="installer-list-actions">';
    echo '<button type="button" class="formButton installer-btn" onclick="setAllModules(1); return false;">'
        . \htmlspecialchars(_AM_MODULEINSTALLER_SELECT_ALL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</button> ';
    echo '<button type="button" class="formButton installer-btn" onclick="setAllModules(0); return false;">'
        . \htmlspecialchars(_AM_MODULEINSTALLER_SELECT_NONE, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</button>';
    echo '</div>';
    echo '<div class="installer-list-toolbar">';
    echo '<label class="installer-filter-label" for="installer-set-module-filter">' . _AM_MODULEINSTALLER_SET_FILTER . '</label> ';
    echo '<input type="text" id="installer-set-module-filter" class="installer-module-filter form-control" size="36" placeholder="'
        . \htmlspecialchars(_AM_MODULEINSTALLER_SET_FILTER_PLACEHOLDER, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" autocomplete="off">';
    echo ' <span class="small installer-filter-hint">' . _AM_MODULEINSTALLER_FILTER_HINT . '</span>';
    echo '</div>';
    echo '</div>';
    echo '<p id="installer-filter-empty" class="x2-note confirmMsg" style="display:none;">' . _AM_MODULEINSTALLER_SET_FILTER_NONE . '</p>';

    $onDisk = $catalog->listOnDisk();
    // Include missing members so user can uncheck/remove them
    $allDirnames = $onDisk;
    foreach (\array_keys($resolvedMissing) as $missingDir) {
        if (! \in_array($missingDir, $allDirnames, true)) {
            $allDirnames[] = $missingDir;
        }
    }
    \sort($allDirnames, SORT_STRING | SORT_FLAG_CASE);

    echo '<div class="installer-module-list installer-set-edit-list">';
    echo '<table class="outer width100 module installer-module-table" cellspacing="1">';
    echo '<thead><tr><th class="installer-col-logo"></th><th>' . _AM_MODULEINSTALLER_SET_MODULE
        . '</th><th>' . _AM_MODULEINSTALLER_SET_STATUS . '</th><th class="center">' . _AM_MODULEINSTALLER_SET_IN_SET . '</th></tr></thead><tbody>';

    $even = false;
    foreach ($allDirnames as $dirname) {
        $even = ! $even;
        $class = $even ? 'even' : 'odd';
        $info = $catalog->loadInfo($dirname);
        $row = $resolver->resolveOne($dirname);
        $inSet = isset($selectedMap[$dirname]);
        $checked = $inSet ? ' checked' : '';
        $rowClass = $class . ' set-mod-row' . ($inSet ? ' installer-row-selected' : '');
        $cellBg = $inSet ? 'background-color:var(--installer-selected,#E6EFC2);' : '';
        $rowStyle = $inSet ? ' style="background-color:var(--installer-selected,#E6EFC2);"' : '';

        $label = null !== $info
            ? \htmlspecialchars($info['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' <span class="small">(/' . \htmlspecialchars($dirname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')</span>'
            : '<code>' . \htmlspecialchars($dirname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>';

        $statusLabel = installer_status_badge($row['state']);

        $img = '';
        if (null !== $info && '' !== $info['image']) {
            $img = '<img class="installer-mod-logo" src="' . \XOOPS_URL . '/modules/' . \rawurlencode($info['dirname']) . '/' . \htmlspecialchars($info['image'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="">';
        }

        $dirnameEsc = \htmlspecialchars($dirname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $searchBlob = \htmlspecialchars(
            \mb_strtolower(($info['name'] ?? '') . ' ' . $dirname . ' ' . ($info['description'] ?? '')),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $tdStyle = $cellBg !== '' ? ' style="' . $cellBg . '"' : '';
        echo '<tr class="' . $rowClass . '"' . $rowStyle . ' data-dirname="' . $dirnameEsc . '" data-search="' . $searchBlob . '">';
        echo '<td class="center img installer-mod-toggle" title="Toggle selection">' . $img . '</td>';
        echo '<td class="installer-mod-toggle installer-mod-desc" title="Toggle selection">' . $label;
        if (null !== $info && '' !== $info['description']) {
            echo '<br><span class="small installer-mod-desc-text">' . \htmlspecialchars($info['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        }
        echo '</td>';
        echo '<td' . $tdStyle . '>' . $statusLabel . '</td>';
        echo '<td class="center installer-set-cb-cell"' . $tdStyle . '><input type="checkbox" class="set-module-cb" name="modules[' . $dirnameEsc . ']" value="1"' . $checked . '></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    echo '<div class="installer-form-actions">';
    echo '<button type="submit" class="formButton">' . _SUBMIT . '</button> ';
    echo '<a class="formButton" href="sets.php">' . _CANCEL . '</a>';
    echo '</div>';
    echo '</form>';

    echo <<<'JS'
<script>
function setSetRowHighlight(row, on) {
    if (!row) {
        return;
    }
    var bg = on ? '#E6EFC2' : '';
    if (on) {
        row.style.setProperty('background-color', '#E6EFC2', 'important');
        row.style.setProperty('background', '#E6EFC2', 'important');
        row.classList.add('installer-row-selected');
    } else {
        row.style.removeProperty('background-color');
        row.style.removeProperty('background');
        row.classList.remove('installer-row-selected');
    }
    var cells = row.querySelectorAll('td');
    for (var c = 0; c < cells.length; c++) {
        if (on) {
            cells[c].style.setProperty('background-color', '#E6EFC2', 'important');
            cells[c].style.setProperty('background', '#E6EFC2', 'important');
        } else {
            cells[c].style.removeProperty('background-color');
            cells[c].style.removeProperty('background');
        }
    }
}
function setAllModules(val) {
    var boxes = document.querySelectorAll('.set-module-cb');
    for (var i = 0; i < boxes.length; i++) {
        var row = boxes[i].closest('tr');
        if (row && row.style.display === 'none') {
            continue;
        }
        boxes[i].checked = !!val;
        setSetRowHighlight(row, !!val);
    }
}
function bindSetModuleRowClicks() {
    var rows = document.querySelectorAll('tr.set-mod-row');
    for (var r = 0; r < rows.length; r++) {
        (function (row) {
            var cb = row.querySelector('.set-module-cb');
            if (!cb) {
                return;
            }
            // Re-apply highlight for rows already checked when the set was opened
            // (admin theme even/odd rules can hide server-side row styles).
            if (cb.checked) {
                setSetRowHighlight(row, true);
            }
            cb.addEventListener('change', function () {
                setSetRowHighlight(row, cb.checked);
            });
            var toggles = row.querySelectorAll('.installer-mod-toggle');
            for (var t = 0; t < toggles.length; t++) {
                toggles[t].addEventListener('click', function (e) {
                    if (e.target && e.target.closest && e.target.closest('input')) {
                        return;
                    }
                    cb.checked = !cb.checked;
                    setSetRowHighlight(row, cb.checked);
                });
            }
        })(rows[r]);
    }
}
function filterSetModules(q) {
    q = (q || '').toLowerCase().trim();
    var rows = document.querySelectorAll('tr.set-mod-row');
    var visible = 0;
    for (var r = 0; r < rows.length; r++) {
        var hay = (rows[r].getAttribute('data-search') || '').toLowerCase();
        var show = !q || hay.indexOf(q) !== -1;
        rows[r].style.display = show ? '' : 'none';
        if (show) visible++;
    }
    var empty = document.getElementById('installer-filter-empty');
    if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
}
function bindSetFilter() {
    var f = document.getElementById('installer-set-module-filter');
    if (f && !f._bound) {
        f._bound = true;
        f.addEventListener('input', function () { filterSetModules(f.value); });
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        bindSetModuleRowClicks();
        bindSetFilter();
    });
} else {
    bindSetModuleRowClicks();
    bindSetFilter();
}
</script>
JS;
}

/**
 * Apply action form with dry-run preview.
 */
function renderApplyForm(
    ModuleSetRepository $repository,
    ModuleSetResolver $resolver,
    ModuleSetApplier $applier,
    string $id
): void {
    $adminObject = Admin::getInstance();
    $adminObject->addItemButton(_AM_MODULEINSTALLER_SET_BACK_LIST, 'sets.php', 'list');
    $adminObject->displayButton('left');

    $set = $repository->get($id);
    if (! $set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
        echo "<div class='errorMsg'>" . _AM_MODULEINSTALLER_SET_ERR_NOTFOUND . '</div>';

        return;
    }

    $action = Request::getCmd('action', ModuleSetApplier::ACTION_FOCUS);

    echo '<div class="installer-apply-stack">';
    echo '<h3>' . \sprintf(_AM_MODULEINSTALLER_SET_APPLY_TITLE, \htmlspecialchars($set->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</h3>';
    echo '<p class="installer-apply-intro">' . _AM_MODULEINSTALLER_SET_APPLY_INTRO
        . ' <span class="installer-tip" title="' . \htmlspecialchars(_AM_MODULEINSTALLER_SET_TIP_APPLY, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">?</span></p>';

    $missing = $resolver->countMissing($set);
    if ($missing > 0) {
        echo "<div class='x2-note confirmMsg installer-apply-block'>" . \sprintf(_AM_MODULEINSTALLER_SET_MISSING_COUNT, $missing) . ' ' . _AM_MODULEINSTALLER_SET_MISSING_HINT . '</div>';
    }

    echo '<form method="get" action="sets.php" class="installer-apply-action">';
    echo '<input type="hidden" name="op" value="apply_form">';
    echo '<input type="hidden" name="id" value="' . \htmlspecialchars($set->getId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    echo '<label for="installer-set-action">' . _AM_MODULEINSTALLER_SET_ACTION . '</label> ';
    echo '<select id="installer-set-action" name="action" onchange="this.form.submit()">';
    $actions = [
        ModuleSetApplier::ACTION_FOCUS => _AM_MODULEINSTALLER_SET_ACT_FOCUS,
        ModuleSetApplier::ACTION_ACTIVATE => _AM_MODULEINSTALLER_SET_ACT_ACTIVATE,
        ModuleSetApplier::ACTION_DEACTIVATE => _AM_MODULEINSTALLER_SET_ACT_DEACTIVATE,
        ModuleSetApplier::ACTION_INSTALL => _AM_MODULEINSTALLER_SET_ACT_INSTALL,
        ModuleSetApplier::ACTION_UNINSTALL => _AM_MODULEINSTALLER_SET_ACT_UNINSTALL,
    ];
    foreach ($actions as $key => $label) {
        $sel = $key === $action ? ' selected' : '';
        echo '<option value="' . \htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' . $sel . '>'
            . \htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
    }
    echo '</select>';
    if (ModuleSetApplier::ACTION_FOCUS === $action) {
        echo ' <span class="installer-tip" title="' . \htmlspecialchars(_AM_MODULEINSTALLER_SET_TIP_FOCUS, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">?</span>';
    } elseif (ModuleSetApplier::ACTION_UNINSTALL === $action) {
        echo ' <span class="installer-tip" title="' . \htmlspecialchars(_AM_MODULEINSTALLER_SET_TIP_UNINSTALL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">?</span>';
    }
    echo '</form>';

    $plan = $applier->plan($set, $action);
    echo '<h4>' . _AM_MODULEINSTALLER_SET_PREVIEW . '</h4>';
    if ([] === $plan) {
        echo "<div class='x2-note confirmMsg'>" . _AM_MODULEINSTALLER_SET_PREVIEW_EMPTY . '</div>';
    } else {
        $counts = installer_plan_counts($plan);
        echo '<div class="installer-plan-summary">' . \sprintf(
            _AM_MODULEINSTALLER_SET_PLAN_SUMMARY,
            $counts['activate'],
            $counts['deactivate'],
            $counts['install'],
            $counts['uninstall'],
            $counts['other']
        ) . '</div>';
        echo '<table class="outer width100" cellspacing="1"><thead><tr>';
        echo '<th>' . _AM_MODULEINSTALLER_SET_MODULE . '</th><th>' . _AM_MODULEINSTALLER_SET_ACTION . '</th><th>' . _AM_MODULEINSTALLER_SET_REASON . '</th>';
        echo '</tr></thead><tbody>';
        $even = false;
        foreach ($plan as $step) {
            $even = ! $even;
            $class = $even ? 'even' : 'odd';
            echo '<tr class="' . $class . '">';
            echo '<td><code>' . \htmlspecialchars($step['dirname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></td>';
            echo '<td>' . \htmlspecialchars($step['action'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            echo '<td>' . \htmlspecialchars($step['reason'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<p class="installer-apply-count">' . \sprintf(_AM_MODULEINSTALLER_SET_PREVIEW_COUNT, \count($plan)) . '</p>';
    }

    // Resolved member summary
    echo '<h4>' . _AM_MODULEINSTALLER_SET_MEMBER_STATUS . '</h4>';
    echo '<table class="outer width100" cellspacing="1"><thead><tr>';
    echo '<th>' . _AM_MODULEINSTALLER_SET_MODULE . '</th><th>' . _AM_MODULEINSTALLER_SET_STATUS . '</th><th>' . _AM_MODULEINSTALLER_SET_NOTICE . '</th>';
    echo '</tr></thead><tbody>';
    $even = false;
    foreach ($resolver->resolve($set) as $row) {
        $even = ! $even;
        $class = $even ? 'even' : 'odd';
        echo '<tr class="' . $class . '">';
        echo '<td><code>' . \htmlspecialchars($row['dirname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></td>';
        echo '<td>' . installer_status_badge($row['state']) . '</td>';
        echo '<td>' . \htmlspecialchars((string) ($row['notice'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    if ([] !== $plan) {
        echo '<form method="post" action="sets.php" class="installer-apply-run">';
        echo $GLOBALS['xoopsSecurity']->getTokenHTML();
        echo '<input type="hidden" name="op" value="apply">';
        echo '<input type="hidden" name="id" value="' . \htmlspecialchars($set->getId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        echo '<input type="hidden" name="action" value="' . \htmlspecialchars($action, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        if (ModuleSetApplier::ACTION_FOCUS === $action) {
            echo '<p class="installer-apply-confirm"><label><input type="checkbox" name="confirm" value="1"> '
                . _AM_MODULEINSTALLER_SET_CONFIRM_FOCUS . '</label></p>';
        } elseif (ModuleSetApplier::ACTION_UNINSTALL === $action) {
            echo '<p class="installer-apply-confirm"><label><input type="checkbox" name="confirm" value="1"> '
                . _AM_MODULEINSTALLER_SET_CONFIRM_UNINSTALL . '</label></p>';
        }
        echo '<p class="installer-apply-buttons">';
        echo '<button type="submit" class="formButton">' . _AM_MODULEINSTALLER_SET_RUN . '</button> ';
        echo '<a class="formButton" href="sets.php">' . _CANCEL . '</a>';
        echo '</p>';
        echo '</form>';
    }
    echo '</div>'; // .installer-apply-stack
}

/**
 * Delete confirmation.
 */
function renderDeleteConfirm(ModuleSetRepository $repository, string $id): void
{
    $set = $repository->get($id);
    if (! $set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
        echo "<div class='errorMsg'>" . _AM_MODULEINSTALLER_SET_ERR_NOTFOUND . '</div>';

        return;
    }

    echo '<h3>' . _DELETE . '</h3>';
    echo '<p>' . \sprintf(_AM_MODULEINSTALLER_SET_DELETE_CONFIRM, \htmlspecialchars($set->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
    echo '<form method="post" action="sets.php">';
    echo $GLOBALS['xoopsSecurity']->getTokenHTML();
    echo '<input type="hidden" name="op" value="delete">';
    echo '<input type="hidden" name="id" value="' . \htmlspecialchars($set->getId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    echo '<button type="submit" class="formButton">' . _DELETE . '</button> ';
    echo '<a class="formButton" href="sets.php">' . _CANCEL . '</a>';
    echo '</form>';
}

/**
 * Duplicate form.
 */
function renderDuplicateForm(ModuleSetRepository $repository, string $id): void
{
    $set = $repository->get($id);
    if (! $set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
        echo "<div class='errorMsg'>" . _AM_MODULEINSTALLER_SET_ERR_NOTFOUND . '</div>';

        return;
    }

    $defaultName = $set->getName() . ' (copy)';
    echo '<h3>' . _AM_MODULEINSTALLER_SET_DUPLICATE . '</h3>';
    echo '<form method="post" action="sets.php">';
    echo $GLOBALS['xoopsSecurity']->getTokenHTML();
    echo '<input type="hidden" name="op" value="duplicate">';
    echo '<input type="hidden" name="id" value="' . \htmlspecialchars($set->getId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    echo '<p><label>' . _AM_MODULEINSTALLER_SET_NAME . ' <input type="text" name="name" size="50" value="'
        . \htmlspecialchars($defaultName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" required></label></p>';
    echo '<button type="submit" class="formButton">' . _AM_MODULEINSTALLER_SET_DUPLICATE . '</button> ';
    echo '<a class="formButton" href="sets.php">' . _CANCEL . '</a>';
    echo '</form>';
}

/**
 * Colored status badge HTML for set member state.
 */
function installer_status_badge(string $state): string
{
    $map = [
        ModuleSetResolver::STATE_ACTIVE => [_AM_MODULEINSTALLER_BADGE_ACTIVE, 'active'],
        ModuleSetResolver::STATE_INACTIVE => [_AM_MODULEINSTALLER_BADGE_INACTIVE, 'inactive'],
        ModuleSetResolver::STATE_NOT_INSTALLED => [_AM_MODULEINSTALLER_BADGE_NOT_INSTALLED, 'notinst'],
        ModuleSetResolver::STATE_MISSING => [_AM_MODULEINSTALLER_BADGE_MISSING, 'missing'],
        ModuleSetResolver::STATE_PROTECTED => [_AM_MODULEINSTALLER_BADGE_PROTECTED, 'protected'],
    ];
    [$label, $css] = $map[$state] ?? [$state, 'inactive'];

    return '<span class="installer-badge installer-badge-' . \htmlspecialchars($css, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
        . \htmlspecialchars((string) $label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
}

/**
 * @param list<array{dirname:string,action:string,reason:string}> $plan
 * @return array{activate:int,deactivate:int,install:int,uninstall:int,other:int}
 */
function installer_plan_counts(array $plan): array
{
    $counts = [
        'activate' => 0,
        'deactivate' => 0,
        'install' => 0,
        'uninstall' => 0,
        'other' => 0,
    ];
    foreach ($plan as $step) {
        $a = \mb_strtolower((string) ($step['action'] ?? ''));
        if (isset($counts[$a])) {
            ++$counts[$a];
        } else {
            ++$counts['other'];
        }
    }

    return $counts;
}

/**
 * Download set as YAML attachment.
 */
function installer_export_set(ModuleSetRepository $repository, string $id): void
{
    $set = $repository->get($id);
    if (! $set instanceof \XoopsModules\Moduleinstaller\Set\ModuleSet) {
        redirect_header('sets.php', 2, _AM_MODULEINSTALLER_SET_ERR_NOTFOUND);

        return;
    }
    $data = $set->toArray();
    // Prefer Xmf\Yaml dump if available
    $yaml = '';
    if (\class_exists(\Xmf\Yaml::class)) {
        try {
            $yaml = \Xmf\Yaml::dump($data);
        } catch (Throwable) {
            $yaml = '';
        }
    }
    if ('' === $yaml || false === $yaml) {
        $yaml = 'id: ' . $data['id'] . "\n"
            . 'name: ' . \str_replace(["\n", "\r"], ' ', $data['name']) . "\n"
            . 'description: ' . \str_replace(["\n", "\r"], ' ', $data['description']) . "\n"
            . "modules:\n";
        foreach ($data['modules'] as $m) {
            $yaml .= '  - ' . $m . "\n";
        }
    }
    $filename = 'module-set-' . $set->getId() . '.yml';
    if (! \headers_sent()) {
        \header('Content-Type: text/yaml; charset=UTF-8');
        \header('Content-Disposition: attachment; filename="' . $filename . '"');
        \header('Content-Length: ' . \strlen((string) $yaml));
    }
    echo $yaml;
    exit;
}

/**
 * Import a set from YAML text.
 *
 * @throws RuntimeException|InvalidArgumentException
 */
function installer_import_set_from_yaml(ModuleSetRepository $repository, string $raw): ModuleSet
{
    $raw = \trim($raw);
    if ('' === $raw) {
        throw new RuntimeException(_AM_MODULEINSTALLER_SET_ERR_IMPORT);
    }
    $data = null;
    if (\class_exists(\Xmf\Yaml::class)) {
        try {
            $data = \Xmf\Yaml::load($raw);
        } catch (Throwable) {
            $data = null;
        }
    }
    if (! \is_array($data)) {
        // Minimal fallback parse for modules: list
        $data = [];
        if (1 === \preg_match('/^name:\s*(.+)$/mi', $raw, $m)) {
            $data['name'] = \trim($m[1], " \t\"'");
        }
        if (1 === \preg_match('/^description:\s*(.+)$/mi', $raw, $m)) {
            $data['description'] = \trim($m[1], " \t\"'");
        }
        $mods = [];
        if (\preg_match_all('/^\s*-\s+([a-zA-Z0-9_-]+)\s*$/m', $raw, $mm) > 0) {
            $mods = $mm[1];
        }
        $data['modules'] = $mods;
    }
    if (! isset($data['name']) || '' === \trim((string) $data['name'])) {
        throw new RuntimeException(_AM_MODULEINSTALLER_SET_ERR_IMPORT);
    }
    $name = (string) $data['name'];
    $desc = (string) ($data['description'] ?? '');
    $mods = [];
    if (isset($data['modules']) && \is_array($data['modules'])) {
        foreach ($data['modules'] as $m) {
            if (\is_string($m) && '' !== \trim($m)) {
                $mods[] = \trim($m);
            }
        }
    }
    $id = $repository->uniqueId(ModuleSet::idFromName($name));
    $set = new ModuleSet($id, $name, $desc, $mods);
    $repository->save($set);

    return $set;
}
