<?php declare(strict_types=1);

/**
 * Sample data for ModuleInstaller — sample **module sets** (YAML), not DB tables.
 *
 * Uses the mtools TestdataButtons contract (op=load|save|clear) so admin home
 * Load / Save / Clear sample buttons work like other mtools consumers.
 *
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GNU GPL
 * @author    Michael Beck (aka Mamba)
 */

use Xmf\Request;
use Xmf\Yaml;
use XoopsModules\Moduleinstaller\Helper;
use XoopsModules\Moduleinstaller\Set\ModuleSet;
use XoopsModules\Moduleinstaller\Set\ModuleSetRepository;

require \dirname(__DIR__, 3) . '/include/cp_header.php';
require \dirname(__DIR__) . '/bootstrap.php';

$mtoolsDependencyError = moduleinstaller_mtools_dependency_error();
if ('' !== $mtoolsDependencyError) {
    redirect_header(XOOPS_URL . '/admin.php', 3, $mtoolsDependencyError);
    exit;
}

$op = Request::getCmd('op', '');

$helper = Helper::getInstance();
$helper->loadLanguage('common');
$helper->loadLanguage('admin');

/**
 * Resolve language subfolder under testdata/ (fallback english).
 */
function installer_testdata_lang_dir(): string
{
    global $xoopsConfig;
    $lang = (string) ($xoopsConfig['language'] ?? 'english');
    $path = __DIR__ . '/' . $lang;
    if (\is_dir($path)) {
        return $path;
    }

    return __DIR__ . '/english';
}

/**
 * @return list<string> Absolute paths to sample set YAML files shipped with the module
 */
function installer_sample_set_files(): array
{
    $dir = installer_testdata_lang_dir() . '/sets';
    if (!\is_dir($dir)) {
        return [];
    }
    $files = \glob($dir . '/*.yml') ?: [];
    \sort($files);

    return $files;
}

/**
 * @return list<string> Sample set ids (from shipped YAML filenames / id fields)
 */
function installer_sample_set_ids(): array
{
    $ids = [];
    foreach (installer_sample_set_files() as $file) {
        $data = Yaml::readWrapped($file);
        if (\is_array($data) && !empty($data['id'])) {
            $ids[] = ModuleSet::normalizeId((string) $data['id']);
        } else {
            $ids[] = ModuleSet::normalizeId((string) \pathinfo($file, \PATHINFO_FILENAME));
        }
    }

    return \array_values(\array_filter($ids));
}

function loadSampleData(): void
{
    $repo = new ModuleSetRepository();
    $repo->ensureStorage();

    $files = installer_sample_set_files();
    if ([] === $files) {
        \redirect_header(
            '../admin/index.php',
            3,
            \defined('_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_FAILURE')
                ? \_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_FAILURE
                : 'No sample set files found.'
        );
    }

    $loaded = 0;
    foreach ($files as $file) {
        $data = Yaml::readWrapped($file);
        if (!\is_array($data)) {
            continue;
        }
        if (empty($data['id'])) {
            $data['id'] = \pathinfo($file, \PATHINFO_FILENAME);
        }
        if (empty($data['name'])) {
            $data['name'] = (string) $data['id'];
        }
        try {
            $set = ModuleSet::fromArray($data);
            // Overwrite existing sample set with same id so re-load is idempotent
            $repo->save($set);
            ++$loaded;
        } catch (\Throwable) {
            continue;
        }
    }

    if (0 === $loaded) {
        \redirect_header(
            '../admin/index.php',
            3,
            \_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_FAILURE
        );
    }

    $msg = \_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_SUCCESS;
    if (\defined('_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_COUNT')) {
        $msg = \sprintf(\_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_COUNT, $loaded);
    }
    \redirect_header('../admin/sets.php', 2, $msg);
}

function saveSampleData(): void
{
    $repo = new ModuleSetRepository();
    $sets = $repo->listAll();

    $exportRoot = installer_testdata_lang_dir() . '/Exports-' . \date('Y-m-d-H-i-s');
    if (!@\mkdir($exportRoot, 0777, true) && !\is_dir($exportRoot)) {
        \redirect_header(
            '../admin/index.php',
            3,
            \_CO_MODULEINSTALLER_EXPORT_SCHEMA_ERROR
        );
    }

    $count = 0;
    foreach ($sets as $set) {
        $path = $exportRoot . '/' . $set->getId() . '.yml';
        $ok   = Yaml::save($set->toArray(), $path);
        if (false !== $ok) {
            ++$count;
        }
    }

    $msg = \_CO_MODULEINSTALLER_SAVE_SAMPLEDATA_SUCCESS;
    if (\defined('_CO_MODULEINSTALLER_SAVE_SAMPLEDATA_COUNT')) {
        $msg = \sprintf(\_CO_MODULEINSTALLER_SAVE_SAMPLEDATA_COUNT, $count, $exportRoot);
    }
    \redirect_header('../admin/index.php', 2, $msg);
}

function clearSampleData(): void
{
    $repo    = new ModuleSetRepository();
    $ids     = installer_sample_set_ids();
    $removed = 0;
    foreach ($ids as $id) {
        if ($repo->delete($id)) {
            ++$removed;
        }
    }

    $msg = \_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA_OK;
    if (\defined('_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA_COUNT')) {
        $msg = \sprintf(\_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA_COUNT, $removed);
    }
    \redirect_header('../admin/sets.php', 2, $msg);
}

// --- Route ---
switch ($op) {
    case 'load':
        if (Request::hasVar('ok', 'REQUEST') && 1 === Request::getInt('ok', 0, 'REQUEST')) {
            if (!$GLOBALS['xoopsSecurity']->check()) {
                redirect_header($helper->url('admin/index.php'), 3, \implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
            }
            loadSampleData();
        } else {
            xoops_cp_header();
            xoops_confirm(
                ['ok' => 1, 'op' => 'load'],
                'index.php',
                \_CO_MODULEINSTALLER_LOAD_SAMPLEDATA_CONFIRM,
                \_CO_MODULEINSTALLER_CONFIRM,
                true
            );
            xoops_cp_footer();
        }
        break;

    case 'save':
        if (Request::hasVar('ok', 'REQUEST') && 1 === Request::getInt('ok', 0, 'REQUEST')) {
            if (!$GLOBALS['xoopsSecurity']->check()) {
                redirect_header($helper->url('admin/index.php'), 3, \implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
            }
            saveSampleData();
        } else {
            xoops_cp_header();
            xoops_confirm(
                ['ok' => 1, 'op' => 'save'],
                'index.php',
                \_CO_MODULEINSTALLER_SAVE_SAMPLEDATA_CONFIRM,
                \_CO_MODULEINSTALLER_CONFIRM,
                true
            );
            xoops_cp_footer();
        }
        break;

    case 'clear':
        if (Request::hasVar('ok', 'REQUEST') && 1 === Request::getInt('ok', 0, 'REQUEST')) {
            if (!$GLOBALS['xoopsSecurity']->check()) {
                redirect_header($helper->url('admin/index.php'), 3, \implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
            }
            clearSampleData();
        } else {
            xoops_cp_header();
            xoops_confirm(
                ['ok' => 1, 'op' => 'clear'],
                'index.php',
                \_CO_MODULEINSTALLER_CLEAR_SAMPLEDATA_CONFIRM,
                \_CO_MODULEINSTALLER_CONFIRM,
                true
            );
            xoops_cp_footer();
        }
        break;

    default:
        redirect_header($helper->url('admin/index.php'), 1, '');
}
