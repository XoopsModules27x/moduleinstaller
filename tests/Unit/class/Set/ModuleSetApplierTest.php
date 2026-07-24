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
        $catalog = new class extends ModuleCatalog {
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
                    return \array_values(\array_filter($all, fn(string $d): bool => $this->isActive($d)));
                }
                if (false === $activeOnly) {
                    return \array_values(\array_filter($all, fn(string $d): bool => !$this->isActive($d)));
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

        $repo    = new ModuleSetRepository($this->tmpDir);
        $applier = new ModuleSetApplier($actions, new ModuleSetResolver($catalog), $repo);
        $set     = new ModuleSet('sim', 'SIM', '', ['party', 'billing', 'deleted_gone']);

        $plan  = $applier->plan($set, ModuleSetApplier::ACTION_FOCUS);
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
}
