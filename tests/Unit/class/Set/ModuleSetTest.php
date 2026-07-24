<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller\Set;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\Set\ModuleSet;

#[CoversClass(ModuleSet::class)]
final class ModuleSetTest extends TestCase
{
    #[Test]
    public function normalizesIdAndModules(): void
    {
        $set = new ModuleSet('My Set!!', 'Demo', 'desc', ['zebra', 'alpha', 'alpha', 'bad name', '', ['dirname' => 'party']]);

        self::assertSame('my-set', $set->getId());
        self::assertSame(['alpha', 'party', 'zebra'], $set->getModules());
        self::assertTrue($set->hasModule('party'));
        self::assertFalse($set->hasModule('missing'));
        self::assertSame(3, $set->countModules());
    }

    #[Test]
    public function fromAndToArrayRoundTrip(): void
    {
        $set  = new ModuleSet('sim-kit', 'SIM Kit', 'Focus SIM', ['party', 'billing']);
        $copy = ModuleSet::fromArray($set->toArray());

        self::assertSame($set->getId(), $copy->getId());
        self::assertSame($set->getName(), $copy->getName());
        self::assertSame($set->getDescription(), $copy->getDescription());
        self::assertSame($set->getModules(), $copy->getModules());
    }

    #[Test]
    public function withModulesReturnsUpdatedCopy(): void
    {
        $set  = new ModuleSet('a', 'A', '', ['one']);
        $next = $set->withModules(['two', 'three']);

        self::assertSame(['one'], $set->getModules());
        self::assertSame(['three', 'two'], $next->getModules());
        self::assertSame('a', $next->getId());
    }

    #[Test]
    public function idFromNameFallsBackWhenEmpty(): void
    {
        $id = ModuleSet::idFromName('!!!');
        self::assertStringStartsWith('set-', $id);
    }
}
