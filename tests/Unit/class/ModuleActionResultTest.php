<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\ModuleActionResult;

#[CoversClass(ModuleActionResult::class)]
final class ModuleActionResultTest extends TestCase
{
    #[Test]
    public function statusHelpers(): void
    {
        $ok = new ModuleActionResult('a', ModuleActionResult::STATUS_OK, 'done', 'activate');
        self::assertTrue($ok->isOk());
        self::assertFalse($ok->isSkip());
        self::assertFalse($ok->isFail());

        $skip = new ModuleActionResult('b', ModuleActionResult::STATUS_SKIP, 'missing', 'activate');
        self::assertTrue($skip->isSkip());

        $fail = new ModuleActionResult('c', ModuleActionResult::STATUS_FAIL, 'boom', 'install');
        self::assertTrue($fail->isFail());
    }
}
