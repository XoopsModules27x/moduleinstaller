<?php declare(strict_types=1);

namespace Tests\Unit;

use ModuleinstallerCorePreload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class ModuleinstallerCorePreloadTest.
 */
#[CoversClass(ModuleinstallerCorePreload::class)]
final class ModuleinstallerCorePreloadTest extends TestCase
{
    use \RequiresXoops;

    private ModuleinstallerCorePreload $moduleinstallerCorePreload;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ModuleinstallerCorePreload extends \XoopsPreloadItem (core), so this needs
        // a booted XOOPS runtime; skip in unit-only mode.
        $this->requiresXoops();

        /** @todo Correctly instantiate tested object to use it. */
        $this->moduleinstallerCorePreload = new ModuleinstallerCorePreload();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->moduleinstallerCorePreload);
    }

    public function testEventCoreIncludeCommonEnd(): void
    {
        /** @todo This test is incomplete. */
        self::markTestIncomplete();
    }
}
