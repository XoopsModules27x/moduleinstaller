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

use XoopsModules\Moduleinstaller\ModuleActionResult;
use XoopsModules\Moduleinstaller\ModuleActionService;
use XoopsModules\Moduleinstaller\ModuleCatalog;

/**
 * Plan and execute bulk actions for a module set.
 */
class ModuleSetApplier
{
    public const ACTION_ACTIVATE   = ModuleActionService::ACTION_ACTIVATE;
    public const ACTION_DEACTIVATE = ModuleActionService::ACTION_DEACTIVATE;
    public const ACTION_INSTALL    = ModuleActionService::ACTION_INSTALL;
    public const ACTION_UNINSTALL  = ModuleActionService::ACTION_UNINSTALL;
    public const ACTION_FOCUS      = 'focus';

    private ModuleActionService $actions;
    private ModuleSetResolver $resolver;
    private ModuleSetRepository $repository;
    private ModuleCatalog $catalog;

    public function __construct(
        ?ModuleActionService $actions = null,
        ?ModuleSetResolver $resolver = null,
        ?ModuleSetRepository $repository = null,
    ) {
        $this->actions    = $actions ?? new ModuleActionService();
        $this->resolver   = $resolver ?? new ModuleSetResolver($this->actions->getCatalog());
        $this->repository = $repository ?? new ModuleSetRepository();
        $this->catalog    = $this->actions->getCatalog();
    }

    /**
     * @return list<string>
     */
    public static function supportedActions(): array
    {
        return [
            self::ACTION_ACTIVATE,
            self::ACTION_DEACTIVATE,
            self::ACTION_INSTALL,
            self::ACTION_UNINSTALL,
            self::ACTION_FOCUS,
        ];
    }

    /**
     * Build a dry-run plan of intended steps.
     *
     * @return list<array{dirname:string,action:string,reason:string}>
     */
    public function plan(ModuleSet $set, string $action): array
    {
        $action = \mb_strtolower(\trim($action));

        return match ($action) {
            self::ACTION_FOCUS      => $this->planFocus($set),
            self::ACTION_ACTIVATE,
            self::ACTION_DEACTIVATE,
            self::ACTION_INSTALL,
            self::ACTION_UNINSTALL  => $this->planMemberAction($set, $action),
            default                 => [],
        };
    }

    /**
     * Execute an action. Optionally snapshot active modules before focus.
     *
     * @return array{results:list<ModuleActionResult>,snapshot_id:?string,notices:list<string>}
     */
    public function execute(ModuleSet $set, string $action, bool $snapshotBeforeFocus = true): array
    {
        $action     = \mb_strtolower(\trim($action));
        $notices    = $this->collectNotices($set);
        $snapshotId = null;
        $results    = [];

        if (self::ACTION_FOCUS === $action && $snapshotBeforeFocus) {
            try {
                $snapshot   = $this->createSnapshot($set);
                $snapshotId = $snapshot->getId();
                $notices[]  = 'Saved snapshot: ' . $snapshot->getName() . ' (' . $snapshotId . ')';
            } catch (\Throwable $e) {
                $notices[] = 'Snapshot failed: ' . $e->getMessage();
            }
        }

        $plan = $this->plan($set, $action);
        foreach ($plan as $step) {
            $results[] = $this->actions->runOne($step['action'], $step['dirname']);
        }

        // Also record skips for missing members not in the plan
        $plannedDirnames = \array_column($plan, 'dirname');
        foreach ($this->resolver->resolve($set) as $dirname => $row) {
            if (ModuleSetResolver::STATE_MISSING === $row['state'] && !\in_array($dirname, $plannedDirnames, true)) {
                $results[] = new ModuleActionResult(
                    $dirname,
                    ModuleActionResult::STATUS_SKIP,
                    (string) ($row['notice'] ?? 'Missing on disk'),
                    $action
                );
            }
        }

        $this->actions->flushCaches();

        return [
            'results'     => $results,
            'snapshot_id' => $snapshotId,
            'notices'     => $notices,
        ];
    }

    /**
     * Snapshot currently active modules so Focus can be undone.
     */
    public function createSnapshot(ModuleSet $beforeFocusSet): ModuleSet
    {
        $active = $this->catalog->listInstalled(true);
        $name   = 'Snapshot before Focus: ' . $beforeFocusSet->getName();
        $id     = $this->repository->uniqueId('snapshot-' . \gmdate('Ymd-His'));

        $set = new ModuleSet(
            $id,
            $name,
            'Auto-created before applying Focus on set "' . $beforeFocusSet->getId() . '"',
            $active,
        );
        $this->repository->save($set);

        return $set;
    }

    /**
     * @return list<string>
     */
    private function collectNotices(ModuleSet $set): array
    {
        $notices = [];
        foreach ($this->resolver->resolve($set) as $row) {
            if (null !== $row['notice'] && ModuleSetResolver::STATE_MISSING === $row['state']) {
                $notices[] = $row['dirname'] . ': ' . $row['notice'];
            }
        }

        return $notices;
    }

    /**
     * @return list<array{dirname:string,action:string,reason:string}>
     */
    private function planMemberAction(ModuleSet $set, string $action): array
    {
        $plan     = [];
        $resolved = $this->resolver->resolve($set);

        foreach ($resolved as $dirname => $row) {
            if ($row['protected'] && \in_array($action, [self::ACTION_UNINSTALL, self::ACTION_DEACTIVATE], true)) {
                continue;
            }
            if (ModuleSetResolver::STATE_MISSING === $row['state']) {
                continue;
            }

            $include = match ($action) {
                self::ACTION_INSTALL    => !$row['installed'] && $row['on_disk'],
                self::ACTION_UNINSTALL  => $row['installed'] && !$row['protected'],
                self::ACTION_ACTIVATE   => $row['installed'] && !$row['active'],
                self::ACTION_DEACTIVATE => $row['installed'] && $row['active'] && !$row['protected'],
                default                 => false,
            };

            if ($include) {
                $plan[] = [
                    'dirname' => $dirname,
                    'action'  => $action,
                    'reason'  => 'Member of set',
                ];
            }
        }

        return $plan;
    }

    /**
     * Focus: activate set members that are installed+inactive; deactivate everything else active (except protected).
     *
     * @return list<array{dirname:string,action:string,reason:string}>
     */
    private function planFocus(ModuleSet $set): array
    {
        $plan        = [];
        $setMembers  = \array_fill_keys($set->getModules(), true);
        $resolved    = $this->resolver->resolve($set);

        // Activate members that are installed but inactive
        foreach ($resolved as $dirname => $row) {
            if ($row['installed'] && !$row['active'] && !$row['protected'] && ModuleSetResolver::STATE_MISSING !== $row['state']) {
                $plan[] = [
                    'dirname' => $dirname,
                    'action'  => self::ACTION_ACTIVATE,
                    'reason'  => 'Focus: activate set member',
                ];
            }
        }

        // Deactivate active modules not in the set
        foreach ($this->catalog->listInstalled(true) as $dirname) {
            if ($this->catalog->isProtected($dirname)) {
                continue;
            }
            if (isset($setMembers[$dirname])) {
                continue;
            }
            $plan[] = [
                'dirname' => $dirname,
                'action'  => self::ACTION_DEACTIVATE,
                'reason'  => 'Focus: deactivate non-member',
            ];
        }

        return $plan;
    }
}
