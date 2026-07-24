<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller\Set;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\ModuleCatalog;
use XoopsModules\Moduleinstaller\Set\ModuleSet;
use XoopsModules\Moduleinstaller\Set\ModuleSetResolver;

#[CoversClass(ModuleSetResolver::class)]
final class ModuleSetResolverTest extends TestCase
{
    #[Test]
    public function resolvesMissingProtectedAndStatesWithoutThrowing(): void
    {
        $catalog = new class extends ModuleCatalog {
            public function isProtected(string $dirname): bool
            {
                return \in_array($dirname, ['system', 'moduleinstaller'], true);
            }

            public function existsOnDisk(string $dirname): bool
            {
                return \in_array($dirname, ['party', 'system', 'notyet'], true);
            }

            public function isInstalled(string $dirname): bool
            {
                return \in_array($dirname, ['party', 'system', 'inactive_mod', 'orphan'], true);
            }

            public function isActive(string $dirname): bool
            {
                return \in_array($dirname, ['party', 'system'], true);
            }
        };

        $resolver = new ModuleSetResolver($catalog);
        $set      = new ModuleSet('t', 'T', '', ['party', 'system', 'deleted_mod', 'notyet', 'inactive_mod', 'orphan']);
        $resolved = $resolver->resolve($set);

        self::assertSame(ModuleSetResolver::STATE_ACTIVE, $resolved['party']['state']);
        self::assertSame(ModuleSetResolver::STATE_PROTECTED, $resolved['system']['state']);
        self::assertSame(ModuleSetResolver::STATE_MISSING, $resolved['deleted_mod']['state']);
        self::assertNotNull($resolved['deleted_mod']['notice']);
        self::assertSame(ModuleSetResolver::STATE_NOT_INSTALLED, $resolved['notyet']['state']);
        self::assertSame(ModuleSetResolver::STATE_INACTIVE, $resolved['inactive_mod']['state']);
        self::assertSame(ModuleSetResolver::STATE_INACTIVE, $resolved['orphan']['state']);
        self::assertStringContainsString('folder missing', (string) $resolved['orphan']['notice']);
        self::assertSame(1, $resolver->countMissing($set));
    }
}
