<?php declare(strict_types=1);

namespace XoopsModules\Moduleinstaller\Set;

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

use XoopsModules\Moduleinstaller\Lang;
use XoopsModules\Moduleinstaller\ModuleCatalog;

/**
 * Resolve set membership against disk + install state.
 *
 * Missing modules produce notices; they never throw.
 */
class ModuleSetResolver
{
    public const STATE_PROTECTED = 'protected';
    public const STATE_MISSING = 'missing';
    public const STATE_ORPHANED = 'orphaned';
    public const STATE_NOT_INSTALLED = 'not_installed';
    public const STATE_INACTIVE = 'inactive';
    public const STATE_ACTIVE = 'active';

    private readonly ModuleCatalog $catalog;

    public function __construct(?ModuleCatalog $catalog = null)
    {
        $this->catalog = $catalog ?? new ModuleCatalog();
    }

    public function getCatalog(): ModuleCatalog
    {
        return $this->catalog;
    }

    /**
     * Resolve each dirname in the set.
     *
     * @return array<string, array{dirname:string,state:string,on_disk:bool,installed:bool,active:bool,protected:bool,notice:?string}>
     */
    public function resolve(ModuleSet $set): array
    {
        $resolved = [];
        foreach ($set->getModules() as $dirname) {
            $resolved[$dirname] = $this->resolveOne($dirname);
        }

        return $resolved;
    }

    /**
     * @return array{dirname:string,state:string,on_disk:bool,installed:bool,active:bool,protected:bool,notice:?string}
     */
    public function resolveOne(string $dirname): array
    {
        $dirname = \trim($dirname);
        $protected = $this->catalog->isProtected($dirname);
        $onDisk = $this->catalog->existsOnDisk($dirname);
        $installed = $this->catalog->isInstalled($dirname);
        $active = $installed && $this->catalog->isActive($dirname);

        // Disk/install reality is decided BEFORE protection so a protected module whose
        // folder was deleted is still surfaced as ORPHANED/MISSING rather than hidden as
        // "Protected". Protection remains available as the orthogonal `protected` flag.
        if ($installed && ! $onDisk) {
            // Installed in the DB but its folder is gone: a distinct state so bulk
            // planning never tries to activate/install a module with no files.
            $state = self::STATE_ORPHANED;
            $notice = Lang::text('_AM_MODULEINSTALLER_RES_ORPHANED', 'Installed in database but folder missing on disk');
        } elseif (! $onDisk && ! $installed) {
            $state = self::STATE_MISSING;
            $notice = Lang::text('_AM_MODULEINSTALLER_RES_REMOVED', 'Not found on disk (removed module)');
        } elseif ($protected) {
            $state = self::STATE_PROTECTED;
            $notice = Lang::text('_AM_MODULEINSTALLER_BADGE_PROTECTED', 'Protected module');
        } elseif (! $installed) {
            // Reached only when on disk (the installed && !on_disk case is ORPHANED above,
            // and !on_disk && !installed is MISSING), so the folder is always present here.
            $state = self::STATE_NOT_INSTALLED;
            $notice = Lang::text('_AM_MODULEINSTALLER_RES_ON_DISK_NOT_INSTALLED', 'Present on disk but not installed');
        } elseif ($active) {
            $state = self::STATE_ACTIVE;
            $notice = null;
        } else {
            $state = self::STATE_INACTIVE;
            $notice = null;
        }

        return [
            'dirname' => $dirname,
            'state' => $state,
            'on_disk' => $onDisk,
            'installed' => $installed,
            'active' => $active,
            'protected' => $protected,
            'notice' => $notice,
        ];
    }

    /**
     * Count missing (stale) members in a set.
     */
    public function countMissing(ModuleSet $set): int
    {
        $n = 0;
        foreach ($this->resolve($set) as $row) {
            if (self::STATE_MISSING === $row['state']) {
                ++$n;
            }
        }

        return $n;
    }
}
