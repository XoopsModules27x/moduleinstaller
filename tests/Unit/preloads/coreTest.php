<?php

namespace Tests\Unit;

use ModuleinstallerCorePreload;
use PHPUnit\Framework\TestCase;

/**
 * Class ModuleinstallerCorePreloadTest.
 *
 * @covers \ModuleinstallerCorePreload
 */
final class ModuleinstallerCorePreloadTest extends TestCase
{
    private ModuleinstallerCorePreload $moduleinstallerCorePreload;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

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
        $this->markTestIncomplete();
    }
}
