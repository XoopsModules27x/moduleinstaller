<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller\Set;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\Set\ModuleSet;
use XoopsModules\Moduleinstaller\Set\ModuleSetRepository;

#[CoversClass(ModuleSetRepository::class)]
final class ModuleSetRepositoryTest extends TestCase
{
    private string $tmpDir;
    private ModuleSetRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = \sys_get_temp_dir() . '/mi_sets_' . \bin2hex(\random_bytes(4));
        $this->repo = new ModuleSetRepository($this->tmpDir);
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
    public function saveGetListDeleteDuplicate(): void
    {
        $set = new ModuleSet('sim-kit', 'SIM Kit', 'desc', ['party', 'billing']);
        self::assertTrue($this->repo->save($set));
        self::assertTrue($this->repo->exists('sim-kit'));

        $loaded = $this->repo->get('sim-kit');
        self::assertInstanceOf(ModuleSet::class, $loaded);
        self::assertSame(['billing', 'party'], $loaded->getModules());

        $all = $this->repo->listAll();
        self::assertCount(1, $all);

        $copy = $this->repo->duplicate('sim-kit', 'SIM Kit Copy');
        self::assertNotSame('sim-kit', $copy->getId());
        self::assertSame(['billing', 'party'], $copy->getModules());
        self::assertCount(2, $this->repo->listAll());

        self::assertTrue($this->repo->delete('sim-kit'));
        self::assertNull($this->repo->get('sim-kit'));
        self::assertCount(1, $this->repo->listAll());
    }

    #[Test]
    public function uniqueIdAvoidsCollision(): void
    {
        $this->repo->save(new ModuleSet('demo', 'Demo', '', ['a']));
        self::assertSame('demo-2', $this->repo->uniqueId('demo'));
    }

    #[Test]
    public function createFromModules(): void
    {
        $set = $this->repo->createFromModules('Active', ['system', 'publisher'], 'snap');
        self::assertSame('Active', $set->getName());
        self::assertSame(['publisher', 'system'], $set->getModules());
        self::assertNotNull($this->repo->get($set->getId()));
    }
}
