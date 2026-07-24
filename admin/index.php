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


use Xmf\Module\Admin;
use Xmf\Request;
use XoopsModules\Moduleinstaller\AdminBulkPage;
use XoopsModules\Moduleinstaller\Helper;
use XoopsModules\Moduleinstaller\ModuleCatalog;
use XoopsModules\Moduleinstaller\Set\ModuleSetRepository;
use XoopsModules\Mtools\Common\TestdataButtons;

require_once __DIR__ . '/admin_header.php';
xoops_cp_header();
AdminBulkPage::ensureAssets();

$adminObject = Admin::getInstance();
$helper      = Helper::getInstance();
$adminObject->displayNavigation(basename(__FILE__));

// mtools sample-data buttons (Load / Save / Clear sample module sets)
// Always register so hide/show toggle works; flag comes from displaySampleButton preference or admin.yml
TestdataButtons::loadButtonConfig($adminObject, $helper);
$adminObject->displayButton('left', '');
$op = Request::getString('op', '', 'GET');
switch ($op) {
    case 'hide_buttons':
        TestdataButtons::hideButtons($helper);
        break;
    case 'show_buttons':
        TestdataButtons::showButtons($helper);
        break;
}

$catalog = new ModuleCatalog();
$repo    = new ModuleSetRepository();
$sets    = [];
try {
    $repo->ensureStorage();
    $sets = $repo->listAll();
} catch (Throwable) {
    $sets = [];
}

$needsUpdate = $catalog->listNeedsUpdate();
$active      = $catalog->listInstalled(true);
$installed   = $catalog->listInstalled();
$onDisk      = $catalog->listOnDisk();

$lastSnapId = isset($_SESSION['installer_last_snapshot_id'])
    ? (string) $_SESSION['installer_last_snapshot_id']
    : '';
$lastSnap = ('' !== $lastSnapId) ? $repo->get($lastSnapId) : null;

echo '<h3>' . _AM_MODULEINSTALLER_DASH_TITLE . '</h3>';
echo '<div class="installer-dash-grid">';

echo '<div class="installer-dash-card"><h4>' . _AM_MODULEINSTALLER_DASH_SETS . '</h4>';
echo '<div class="num">' . \count($sets) . '</div>';
echo '<p>' . \sprintf(_AM_MODULEINSTALLER_DASH_SETS_COUNT, \count($sets)) . '</p>';
echo '<p><a href="sets.php">' . _AM_MODULEINSTALLER_DASH_GO_SETS . '</a></p></div>';

echo '<div class="installer-dash-card"><h4>' . _AM_MODULEINSTALLER_DASH_UPDATES . '</h4>';
echo '<div class="num">' . \count($needsUpdate) . '</div>';
echo '<p>' . \sprintf(_AM_MODULEINSTALLER_DASH_UPDATES_COUNT, \count($needsUpdate)) . '</p>';
echo '<p><a href="update.php?needs_only=1">' . _AM_MODULEINSTALLER_DASH_GO_UPDATE . '</a></p></div>';

echo '<div class="installer-dash-card"><h4>' . _AM_MODULEINSTALLER_DASH_ACTIVE . '</h4>';
echo '<div class="num">' . \count($active) . '</div></div>';

echo '<div class="installer-dash-card"><h4>' . _AM_MODULEINSTALLER_DASH_INSTALLED . '</h4>';
echo '<div class="num">' . \count($installed) . '</div></div>';

echo '<div class="installer-dash-card"><h4>' . _AM_MODULEINSTALLER_DASH_ONDISK . '</h4>';
echo '<div class="num">' . \count($onDisk) . '</div></div>';

echo '<div class="installer-dash-card"><h4>' . _AM_MODULEINSTALLER_DASH_LAST_SNAPSHOT . '</h4>';
if ($lastSnap) {
    echo '<p><strong>' . \htmlspecialchars($lastSnap->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong></p>';
    echo '<p><a href="sets.php?op=apply_form&amp;id=' . \rawurlencode($lastSnap->getId()) . '&amp;action=focus">'
        . _AM_MODULEINSTALLER_SET_RESTORE_SNAPSHOT . '</a></p>';
} else {
    echo '<p class="small">' . _AM_MODULEINSTALLER_DASH_NO_SNAPSHOT . '</p>';
}
echo '</div>';

echo '</div>'; // grid
// Quick links removed — nav tabs / ModuleAdmin buttons cover the same destinations

$adminObject->displayIndex();

require_once __DIR__ . '/admin_footer.php';
