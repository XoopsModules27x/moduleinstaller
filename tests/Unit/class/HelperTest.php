<?php

declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\Helper;

/**
 * Class HelperTest.
 */
#[CoversClass(Helper::class)]
final class HelperTest extends TestCase
{
    use \RequiresXoops;

    private Helper $helper;

    private bool $debug;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Helper extends Xmf\Module\Helper, whose constructor calls xoops_getHandler();
        // that needs a booted XOOPS runtime, so skip in unit-only mode.
        $this->requiresXoops();

        $this->debug = true;
        $this->helper = new Helper($this->debug);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->helper);
        unset($this->debug);
    }

    public function testGetInstance(): void
    {
        /** @todo This test is incomplete. */
        self::markTestIncomplete();
    }

    public function testGetDirname(): void
    {
        /** @todo This test is incomplete. */
        self::markTestIncomplete();
    }

    public function testGetHandler(): void
    {
        /** @todo This test is incomplete. */
        self::markTestIncomplete();
    }
}
