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
 * Safe bulk install/uninstall/activate/deactivate/update for modules.
 *
 * Never fatals on missing modules; returns skip/fail results and continues.
 */
class ModuleActionService
{
    public const ACTION_INSTALL = 'install';
    public const ACTION_UNINSTALL = 'uninstall';
    public const ACTION_ACTIVATE = 'activate';
    public const ACTION_DEACTIVATE = 'deactivate';
    public const ACTION_UPDATE = 'update';

    private readonly ModuleCatalog $catalog;
    private bool $coreLoaded = false;

    public function __construct(?ModuleCatalog $catalog = null)
    {
        $this->catalog = $catalog ?? new ModuleCatalog();
    }

    public function getCatalog(): ModuleCatalog
    {
        return $this->catalog;
    }

    /**
     * Ensure system modulesadmin helpers are loaded.
     */
    public function loadCoreAdmin(): void
    {
        if ($this->coreLoaded) {
            return;
        }

        require_once \XOOPS_ROOT_PATH . '/class/xoopsblock.php';
        require_once \XOOPS_ROOT_PATH . '/kernel/module.php';
        require_once \XOOPS_ROOT_PATH . '/include/cp_functions.php';
        require_once \XOOPS_ROOT_PATH . '/include/version.php';
        require_once \XOOPS_ROOT_PATH . '/modules/system/admin/modulesadmin/modulesadmin.php';

        \xoops_loadLanguage('global');
        \xoops_loadLanguage('admin/modulesadmin', 'system');

        $this->coreLoaded = true;
    }

    /**
     * Extract selected dirnames from a modules[dirname] => 0|1 map (form POST).
     *
     * @param array<string, mixed> $modulesMap
     * @return list<string>
     */
    public function selectedDirnames(array $modulesMap): array
    {
        $selected = [];
        foreach ($modulesMap as $dirname => $flag) {
            if ('' === (string) $dirname) {
                continue;
            }
            if ((int) $flag === 1 || true === $flag || '1' === $flag || 'yes' === \mb_strtolower((string) $flag)) {
                $selected[] = (string) $dirname;
            }
        }

        return $selected;
    }

    /**
     * Run one action against many dirnames. Continues on individual failures.
     *
     * @param list<string> $dirnames
     * @return list<ModuleActionResult>
     */
    public function runMany(string $action, array $dirnames): array
    {
        $results = [];
        foreach ($dirnames as $dirname) {
            $results[] = $this->runOne($action, (string) $dirname);
        }

        return $results;
    }

    /**
     * Run action for a single module dirname.
     */
    public function runOne(string $action, string $dirname): ModuleActionResult
    {
        $dirname = \trim($dirname);
        $action = \mb_strtolower(\trim($action));

        if ('' === $dirname) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Empty dirname', $action);
        }

        if ($this->catalog->isProtected($dirname) && \in_array($action, [self::ACTION_UNINSTALL, self::ACTION_DEACTIVATE], true)) {
            return new ModuleActionResult(
                $dirname,
                ModuleActionResult::STATUS_SKIP,
                \sprintf('Protected module "%s" cannot be %sd', $dirname, $action),
                $action
            );
        }

        try {
            $this->loadCoreAdmin();

            return match ($action) {
                self::ACTION_INSTALL => $this->doInstall($dirname),
                self::ACTION_UNINSTALL => $this->doUninstall($dirname),
                self::ACTION_ACTIVATE => $this->doActivate($dirname),
                self::ACTION_DEACTIVATE => $this->doDeactivate($dirname),
                self::ACTION_UPDATE => $this->doUpdate($dirname),
                default => new ModuleActionResult($dirname, ModuleActionResult::STATUS_FAIL, 'Unknown action: ' . $action, $action),
            };
        } catch (\Throwable $e) {
            return new ModuleActionResult(
                $dirname,
                ModuleActionResult::STATUS_FAIL,
                'Exception: ' . $e->getMessage(),
                $action
            );
        }
    }

    /**
     * Flush cpanel and active-modules caches after bulk ops.
     */
    public function flushCaches(): void
    {
        \xoops_load('cpanel', 'system');
        if (\class_exists('XoopsSystemCpanel', false)) {
            \XoopsSystemCpanel::flush();
        }
        if (\function_exists('xoops_setActiveModules')) {
            \xoops_setActiveModules();
        }
    }

    private function doInstall(string $dirname): ModuleActionResult
    {
        if (! $this->catalog->existsOnDisk($dirname)) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Not found on disk', self::ACTION_INSTALL);
        }
        if ($this->catalog->isInstalled($dirname)) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Already installed', self::ACTION_INSTALL);
        }

        $msg = \xoops_module_install($dirname);

        return $this->verifiedResult($dirname, self::ACTION_INSTALL, (string) $msg, true, null, 'Install did not complete');
    }

    private function doUninstall(string $dirname): ModuleActionResult
    {
        if (! $this->catalog->isInstalled($dirname)) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Not installed', self::ACTION_UNINSTALL);
        }

        $msg = \xoops_module_uninstall($dirname);

        return $this->verifiedResult($dirname, self::ACTION_UNINSTALL, (string) $msg, false, null, 'Uninstall did not complete');
    }

    private function doActivate(string $dirname): ModuleActionResult
    {
        $module = $this->catalog->getInstalled($dirname);
        if (! $module instanceof \XoopsModule) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Not installed (cannot activate)', self::ACTION_ACTIVATE);
        }
        if ($module->isActive()) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Already active', self::ACTION_ACTIVATE);
        }

        $mid = (int) $module->getVar('mid');
        if ($mid <= 0) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_FAIL, 'Invalid module id', self::ACTION_ACTIVATE);
        }

        $msg = \xoops_module_activate($mid);

        return $this->verifiedResult($dirname, self::ACTION_ACTIVATE, (string) $msg, true, true, 'Activation did not take effect');
    }

    private function doDeactivate(string $dirname): ModuleActionResult
    {
        $module = $this->catalog->getInstalled($dirname);
        if (! $module instanceof \XoopsModule) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Not installed (cannot deactivate)', self::ACTION_DEACTIVATE);
        }
        if (! $module->isActive()) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Already inactive', self::ACTION_DEACTIVATE);
        }

        $mid = (int) $module->getVar('mid');
        if ($mid <= 0) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_FAIL, 'Invalid module id', self::ACTION_DEACTIVATE);
        }

        $msg = \xoops_module_deactivate($mid);

        // Core silently refuses to deactivate system/start-page modules; a clean
        // deactivation must leave the module installed AND inactive (not vanished).
        return $this->verifiedResult($dirname, self::ACTION_DEACTIVATE, (string) $msg, true, false, 'Deactivation did not take effect (start-page or protected module?)');
    }

    private function doUpdate(string $dirname): ModuleActionResult
    {
        if (! $this->catalog->isInstalled($dirname)) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Not installed', self::ACTION_UPDATE);
        }
        if (! $this->catalog->existsOnDisk($dirname)) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_SKIP, 'Not found on disk', self::ACTION_UPDATE);
        }

        $msg = \xoops_module_update($dirname);

        // NOTE: core xoops_module_update() bumps the DB version and inserts it BEFORE
        // running the module's pre_update callback and never rolls back, so a failed
        // update callback leaves the module installed at the new version. That failure
        // is therefore not detectable from DB state — we can only confirm the module
        // did not vanish.
        return $this->verifiedResult($dirname, self::ACTION_UPDATE, (string) $msg, true, null, 'Update did not complete');
    }

    /**
     * Build a result by comparing the module's real post-action state against what the
     * action was expected to produce.
     *
     * @param bool      $wantInstalled expected installed state after the action
     * @param bool|null $wantActive    expected active state, or null if the action does not change it
     */
    private function verifiedResult(string $dirname, string $action, string $msg, bool $wantInstalled, ?bool $wantActive, string $failReason): ModuleActionResult
    {
        $state = $this->freshState($dirname);
        if (null === $state) {
            // Post-action state could not be read (no DB / query failed). For a
            // destructive bulk tool an unverifiable outcome is reported as a failure so
            // a genuinely failed operation is never silently presented as success.
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_FAIL, 'Result could not be verified (module state unreadable): ' . $msg, $action);
        }

        $reached = $state['installed'] === $wantInstalled
            && (null === $wantActive || $state['active'] === $wantActive);
        if (! $reached) {
            return new ModuleActionResult($dirname, ModuleActionResult::STATUS_FAIL, $failReason . ': ' . $msg, $action);
        }

        return new ModuleActionResult($dirname, ModuleActionResult::STATUS_OK, $msg, $action);
    }

    /**
     * Read a module's installed/active state straight from the DB, bypassing the
     * module handler's per-request static cache (which core install/uninstall/
     * activate/deactivate do NOT invalidate, so a handler re-read returns stale data).
     *
     * @return array{installed:bool,active:bool}|null null when the state cannot be
     *                                                 read (no DB / query failed)
     */
    private function freshState(string $dirname): ?array
    {
        $dirname = \trim($dirname);
        $db = $GLOBALS['xoopsDB'] ?? null;
        if ('' === $dirname || ! $db instanceof \XoopsMySQLDatabase) {
            return null;
        }

        $sql = 'SELECT isactive FROM ' . $db->prefix('modules') . ' WHERE dirname = ' . $db->quote($dirname);
        $result = $db->query($sql);
        if (! $result) {
            return null;
        }
        $row = $db->fetchArray($result);
        if (false === $row) {
            return ['installed' => false, 'active' => false];
        }

        return ['installed' => true, 'active' => 1 === (int) $row['isactive']];
    }
}
