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

class ModuleCatalog
{
    /** @var list<string> */
    public const PROTECTED_DIRNAMES = ['system', 'moduleinstaller'];

    /**
     * @param \XoopsModuleHandler|null $moduleHandler Optional handler (defaults to core)
     */
    public function __construct(private $moduleHandler = null)
    {
    }

    /**
     * @return \XoopsModuleHandler
     */
    private function getModuleHandler()
    {
        if (null === $this->moduleHandler) {
            $this->moduleHandler = xoops_getHandler('module');
        }

        return $this->moduleHandler;
    }

    /**
     * Whether dirname is protected from bulk destructive actions.
     *
     * Besides the hard-coded list, the current site start-page module is protected
     * dynamically: XOOPS core silently refuses to deactivate it, so treating it as
     * protected keeps bulk plans honest instead of reporting a no-op as success.
     */
    public function isProtected(string $dirname): bool
    {
        $dirname = \mb_strtolower(\trim($dirname));

        return \in_array($dirname, self::PROTECTED_DIRNAMES, true)
            || ('' !== $dirname && $dirname === $this->startPageDirname());
    }

    /**
     * Dirname of the configured site start-page module (lower-cased), or '' if unknown.
     */
    private function startPageDirname(): string
    {
        $config = $GLOBALS['xoopsConfig'] ?? null;
        if (\is_array($config) && isset($config['startpage'])) {
            return \mb_strtolower(\trim((string) $config['startpage']));
        }

        return '';
    }

    /**
     * Module folder exists under modules/ with a loadable xoops_version.
     */
    public function existsOnDisk(string $dirname): bool
    {
        $dirname = \trim($dirname);
        if ('' === $dirname || ! \preg_match('/^[a-zA-Z0-9_-]+$/', $dirname)) {
            return false;
        }

        $path = \XOOPS_ROOT_PATH . '/modules/' . $dirname;
        if (! \is_dir($path)) {
            return false;
        }

        $module = $this->getModuleHandler()->create();

        return (bool) $module->loadInfo($dirname, false);
    }

    public function getInstalled(string $dirname): ?\XoopsModule
    {
        $dirname = \trim($dirname);
        if ('' === $dirname) {
            return null;
        }

        $module = $this->getModuleHandler()->getByDirname($dirname);

        return ($module instanceof \XoopsModule) ? $module : null;
    }

    public function isInstalled(string $dirname): bool
    {
        return $this->getInstalled($dirname) instanceof \XoopsModule;
    }

    public function isActive(string $dirname): bool
    {
        $module = $this->getInstalled($dirname);

        return $module instanceof \XoopsModule && $module->isActive();
    }

    /**
     * Dirnames of modules present on disk (from XoopsLists).
     *
     * @return list<string>
     */
    public function listOnDisk(): array
    {
        \XoopsLoad::load('XoopsLists');
        $list = \XoopsLists::getModulesList();
        if (! \is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $dirname) {
            $dirname = \trim((string) $dirname);
            if ('' !== $dirname) {
                $out[] = $dirname;
            }
        }
        \sort($out, \SORT_STRING | \SORT_FLAG_CASE);

        return $out;
    }

    /**
     * Dirnames of installed modules.
     *
     * @param bool|null $activeOnly true = only active, false = only inactive, null = all installed
     * @return list<string>
     */
    public function listInstalled(?bool $activeOnly = null): array
    {
        $modules = $this->getModuleHandler()->getObjects();
        $out = [];
        foreach ($modules as $module) {
            if (! $module instanceof \XoopsModule) {
                continue;
            }
            if (true === $activeOnly && ! $module->isActive()) {
                continue;
            }
            if (false === $activeOnly && $module->isActive()) {
                continue;
            }
            $out[] = (string) $module->getVar('dirname');
        }
        \sort($out, \SORT_STRING | \SORT_FLAG_CASE);

        return $out;
    }

    /**
     * Load display info for a module folder (name, version, image, description).
     *
     * @return array{dirname:string,name:string,version:mixed,module_status:mixed,image:string,description:string}|null
     */
    public function loadInfo(string $dirname): ?array
    {
        $dirname = \trim($dirname);
        if ('' === $dirname) {
            return null;
        }

        $module = $this->getModuleHandler()->create();
        if (! $module->loadInfo($dirname, false)) {
            return null;
        }

        return [
            'dirname' => (string) $module->getInfo('dirname'),
            'name' => (string) $module->getInfo('name'),
            'version' => $module->getInfo('version'),
            'module_status' => $module->getInfo('module_status'),
            'image' => (string) $module->getInfo('image'),
            'description' => (string) $module->getInfo('description'),
        ];
    }

    /**
     * Whether disk xoops_version differs from DB-installed version.
     */
    public function needsUpdate(string $dirname): bool
    {
        $installed = $this->getInstalled($dirname);
        if (! $installed instanceof \XoopsModule || ! $this->existsOnDisk($dirname)) {
            return false;
        }
        $info = $this->loadInfo($dirname);
        if (null === $info) {
            return false;
        }
        $dbVersion = $installed->getVar('version');
        $diskVersion = \str_replace("\n", '', (string) $info['version']);
        $legacyInt = (string) (int) \round(((float) $diskVersion) * 100);

        return ($diskVersion != $dbVersion && $legacyInt !== (string) $dbVersion);
    }

    /**
     * @return list<string>
     */
    public function listNeedsUpdate(): array
    {
        $out = [];
        foreach ($this->listInstalled() as $dirname) {
            if ($this->needsUpdate($dirname)) {
                $out[] = $dirname;
            }
        }

        return $out;
    }

    /**
     * Candidates for a bulk action tab.
     *
     * @return list<string>
     */
    public function candidatesFor(string $action): array
    {
        $action = \mb_strtolower(\trim($action));

        return match ($action) {
            'install' => $this->candidatesInstall(),
            'uninstall' => $this->candidatesUninstall(),
            'activate' => $this->listInstalled(false),
            'deactivate' => $this->candidatesDeactivate(),
            'update' => \array_values(\array_filter(
                $this->listInstalled(),
                $this->existsOnDisk(...)
            )),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function candidatesInstall(): array
    {
        $installed = \array_fill_keys($this->listInstalled(), true);
        $out = [];
        foreach ($this->listOnDisk() as $dirname) {
            if (! isset($installed[$dirname])) {
                $out[] = $dirname;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function candidatesUninstall(): array
    {
        $out = [];
        foreach ($this->listInstalled() as $dirname) {
            if (! $this->isProtected($dirname)) {
                $out[] = $dirname;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function candidatesDeactivate(): array
    {
        $out = [];
        foreach ($this->listInstalled(true) as $dirname) {
            if (! $this->isProtected($dirname)) {
                $out[] = $dirname;
            }
        }

        return $out;
    }
}
