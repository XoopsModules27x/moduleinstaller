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

use XoopsModules\Moduleinstaller\ModuleCatalog;

/**
 * Resolve set membership against disk + install state.
 *
 * Missing modules produce notices; they never throw.
 */
class ModuleSetResolver
{
    public const STATE_PROTECTED     = 'protected';
    public const STATE_MISSING       = 'missing';
    public const STATE_NOT_INSTALLED = 'not_installed';
    public const STATE_INACTIVE      = 'inactive';
    public const STATE_ACTIVE        = 'active';

    private ModuleCatalog $catalog;

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
        $dirname   = \trim($dirname);
        $protected = $this->catalog->isProtected($dirname);
        $onDisk    = $this->catalog->existsOnDisk($dirname);
        $installed = $this->catalog->isInstalled($dirname);
        $active    = $installed && $this->catalog->isActive($dirname);

        if ($protected) {
            $state  = self::STATE_PROTECTED;
            $notice = 'Protected module';
        } elseif (!$onDisk && !$installed) {
            $state  = self::STATE_MISSING;
            $notice = 'Not found on disk (removed module)';
        } elseif (!$installed) {
            $state  = self::STATE_NOT_INSTALLED;
            $notice = $onDisk ? 'Present on disk but not installed' : 'Not installed';
        } elseif ($active) {
            $state  = self::STATE_ACTIVE;
            $notice = null;
        } else {
            $state  = self::STATE_INACTIVE;
            $notice = null;
        }

        // Folder gone but still in DB (orphaned install)
        if ($installed && !$onDisk) {
            $notice = 'Installed in database but folder missing on disk';
        }

        return [
            'dirname'   => $dirname,
            'state'     => $state,
            'on_disk'   => $onDisk,
            'installed' => $installed,
            'active'    => $active,
            'protected' => $protected,
            'notice'    => $notice,
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
