<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller\Set;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\ModuleActionResult;
use XoopsModules\Moduleinstaller\ModuleActionService;
use XoopsModules\Moduleinstaller\ModuleCatalog;
use XoopsModules\Moduleinstaller\Set\ModuleSet;
use XoopsModules\Moduleinstaller\Set\ModuleSetApplier;
use XoopsModules\Moduleinstaller\Set\ModuleSetRepository;
use XoopsModules\Moduleinstaller\Set\ModuleSetResolver;

#[CoversClass(ModuleSetApplier::class)]
final class ModuleSetApplierTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = \sys_get_temp_dir() . '/mi_apply_' . \bin2hex(\random_bytes(4));
    }

    protected function tearDown(): void
    {
        if (\is_dir($this->tmpDir)) {
            foreach (\glob($this->tmpDir . '/*') ?: [] as $file) {
                @\unlink($file);
            }
            @\rmdir($this->tmpDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function planFocusActivatesMembersAndDeactivatesOutsiders(): void
    {
        $catalog = new class () extends ModuleCatalog {
            public function isProtected(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller'], true);
            }

            public function existsOnDisk(string $dirname): bool
            {
                return 'deleted_gone' !== $dirname;
            }

            public function isInstalled(string $dirname): bool
            {
                return 'deleted_gone' !== $dirname;
            }

            public function isActive(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller', 'noise', 'party'], true);
            }

            public function listInstalled(?bool $activeOnly = null): array
            {
                $all = ['system', 'moduleinstaller', 'noise', 'party', 'billing'];
                if (true === $activeOnly) {
                    return \array_values(\array_filter($all, $this->isActive(...)));
                }
                if (false === $activeOnly) {
                    return \array_values(\array_filter($all, fn (string $d): bool => ! $this->isActive($d)));
                }

                return $all;
            }
        };

        $actions = new class ($catalog) extends ModuleActionService {
            public function __construct(ModuleCatalog $cat)
            {
                parent::__construct($cat);
            }

            public function runOne(string $action, string $dirname): ModuleActionResult
            {
                return new ModuleActionResult($dirname, ModuleActionResult::STATUS_OK, 'ok', $action);
            }

            public function flushCaches(): void
            {
            }
        };

        $repo = new ModuleSetRepository($this->tmpDir);
        $applier = new ModuleSetApplier($actions, new ModuleSetResolver($catalog), $repo);
        $set = new ModuleSet('sim', 'SIM', '', ['party', 'billing', 'deleted_gone']);

        $plan = $applier->plan($set, ModuleSetApplier::ACTION_FOCUS);
        $byDir = [];
        foreach ($plan as $step) {
            $byDir[$step['dirname']] = $step['action'];
        }

        self::assertSame(ModuleActionService::ACTION_ACTIVATE, $byDir['billing']);
        self::assertSame(ModuleActionService::ACTION_DEACTIVATE, $byDir['noise']);
        self::assertArrayNotHasKey('party', $byDir); // already active
        self::assertArrayNotHasKey('system', $byDir);
        self::assertArrayNotHasKey('deleted_gone', $byDir);

        $outcome = $applier->execute($set, ModuleSetApplier::ACTION_FOCUS, true);
        self::assertNotNull($outcome['snapshot_id']);
        self::assertNotEmpty($outcome['results']);

        $skipMissing = false;
        foreach ($outcome['results'] as $result) {
            if ('deleted_gone' === $result->dirname && $result->isSkip()) {
                $skipMissing = true;
            }
        }
        self::assertTrue($skipMissing, 'Missing set member should be reported as skip');
    }

    #[Test]
    public function focusAbortsWhenSnapshotCannotBeSaved(): void
    {
        $catalog = new class () extends ModuleCatalog {
            public function isProtected(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller'], true);
            }

            public function existsOnDisk(string $dirname): bool
            {
                return true;
            }

            public function isInstalled(string $dirname): bool
            {
                return true;
            }

            public function isActive(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller', 'noise'], true);
            }

            public function listInstalled(?bool $activeOnly = null): array
            {
                $all = ['system', 'moduleinstaller', 'noise', 'party'];
                if (true === $activeOnly) {
                    return \array_values(\array_filter($all, $this->isActive(...)));
                }
                if (false === $activeOnly) {
                    return \array_values(\array_filter($all, fn (string $d): bool => ! $this->isActive($d)));
                }

                return $all;
            }
        };

        // Records whether any module action was executed.
        $actions = new class ($catalog) extends ModuleActionService {
            public int $ran = 0;

            public function runOne(string $action, string $dirname): ModuleActionResult
            {
                ++$this->ran;

                return new ModuleActionResult($dirname, ModuleActionResult::STATUS_OK, 'ok', $action);
            }

            public function flushCaches(): void
            {
            }
        };

        // Snapshot persistence fails: Focus must abort before touching any module.
        $repo = new class ($this->tmpDir) extends ModuleSetRepository {
            public function save(ModuleSet $set): bool
            {
                throw new \RuntimeException('disk full');
            }
        };

        $applier = new ModuleSetApplier($actions, new ModuleSetResolver($catalog), $repo);
        $set = new ModuleSet('sim', 'SIM', '', ['party']);

        $outcome = $applier->execute($set, ModuleSetApplier::ACTION_FOCUS, true);

        self::assertNull($outcome['snapshot_id']);
        self::assertSame([], $outcome['results']);
        self::assertSame(0, $actions->ran, 'No module action may run once the snapshot has failed');
        self::assertNotEmpty(
            \array_filter($outcome['notices'], static fn (string $n): bool => \str_contains($n, 'Aborted')),
            'An abort notice should explain why nothing ran'
        );
    }

    #[Test]
    public function orphanedInstalledMemberIsNeverActivatedAndIsSkipped(): void
    {
        $catalog = new class () extends ModuleCatalog {
            public function isProtected(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller'], true);
            }

            // 'ghost' is installed in the DB but its folder is gone (orphaned).
            public function existsOnDisk(string $dirname): bool
            {
                return 'ghost' !== $dirname;
            }

            public function isInstalled(string $dirname): bool
            {
                return true;
            }

            public function isActive(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller'], true);
            }

            public function listInstalled(?bool $activeOnly = null): array
            {
                $all = ['system', 'moduleinstaller', 'party', 'ghost'];
                if (true === $activeOnly) {
                    return \array_values(\array_filter($all, $this->isActive(...)));
                }
                if (false === $activeOnly) {
                    return \array_values(\array_filter($all, fn (string $d): bool => ! $this->isActive($d)));
                }

                return $all;
            }
        };

        $actions = new class ($catalog) extends ModuleActionService {
            public function runOne(string $action, string $dirname): ModuleActionResult
            {
                return new ModuleActionResult($dirname, ModuleActionResult::STATUS_OK, 'ok', $action);
            }

            public function flushCaches(): void
            {
            }
        };

        $applier = new ModuleSetApplier($actions, new ModuleSetResolver($catalog), new ModuleSetRepository($this->tmpDir));
        $set = new ModuleSet('sim', 'SIM', '', ['party', 'ghost']);

        $plan = $applier->plan($set, ModuleSetApplier::ACTION_FOCUS);
        foreach ($plan as $step) {
            self::assertNotSame('ghost', $step['dirname'], 'Orphaned module must never appear in the plan');
        }
        $partyActivated = \array_filter(
            $plan,
            static fn (array $s): bool => 'party' === $s['dirname'] && ModuleActionService::ACTION_ACTIVATE === $s['action'],
        );
        self::assertNotEmpty($partyActivated, 'On-disk inactive member should still be activated');

        $outcome = $applier->execute($set, ModuleSetApplier::ACTION_FOCUS, true);
        $ghostSkipped = false;
        foreach ($outcome['results'] as $result) {
            if ('ghost' === $result->dirname && $result->isSkip()) {
                $ghostSkipped = true;
            }
        }
        self::assertTrue($ghostSkipped, 'Orphaned member should be reported as a skip');
    }

    #[Test]
    public function focusActivatesAProtectedButInactiveMember(): void
    {
        // 'startpage_mod' is protected (e.g. the site start page) but currently inactive.
        // Focus should still be able to turn it on — protection only blocks destructive
        // actions, not activation.
        $catalog = new class () extends ModuleCatalog {
            public function isProtected(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller', 'startpage_mod'], true);
            }

            public function existsOnDisk(string $dirname): bool
            {
                return true;
            }

            public function isInstalled(string $dirname): bool
            {
                return true;
            }

            public function isActive(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller'], true);
            }

            public function listInstalled(?bool $activeOnly = null): array
            {
                $all = ['system', 'moduleinstaller', 'startpage_mod'];
                if (true === $activeOnly) {
                    return \array_values(\array_filter($all, $this->isActive(...)));
                }
                if (false === $activeOnly) {
                    return \array_values(\array_filter($all, fn (string $d): bool => ! $this->isActive($d)));
                }

                return $all;
            }
        };

        $actions = new class ($catalog) extends ModuleActionService {
            public function runOne(string $action, string $dirname): ModuleActionResult
            {
                return new ModuleActionResult($dirname, ModuleActionResult::STATUS_OK, 'ok', $action);
            }

            public function flushCaches(): void
            {
            }
        };

        $applier = new ModuleSetApplier($actions, new ModuleSetResolver($catalog), new ModuleSetRepository($this->tmpDir));
        $set = new ModuleSet('sp', 'SP', '', ['startpage_mod']);

        $plan = $applier->plan($set, ModuleSetApplier::ACTION_FOCUS);
        $activated = \array_filter(
            $plan,
            static fn (array $s): bool => 'startpage_mod' === $s['dirname'] && ModuleActionService::ACTION_ACTIVATE === $s['action'],
        );
        self::assertNotEmpty($activated, 'A protected but inactive member should still be activated by Focus');
    }
}
