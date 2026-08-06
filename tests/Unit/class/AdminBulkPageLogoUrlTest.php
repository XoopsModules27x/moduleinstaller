<?php declare(strict_types=1);

namespace Tests\Unit\XoopsModules\Moduleinstaller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use XoopsModules\Moduleinstaller\AdminBulkPage;

/**
 * Rejection contract for the module-logo resolver.
 *
 * A module manifest is third-party PHP, so its `image` value is a hostile string
 * that ends up in an <img src>. Every case below must resolve to null BEFORE any
 * filesystem call, which is what makes them assertable without a booted XOOPS.
 *
 * NOT covered here, and deliberately: the positive path and the image-extension
 * allowlist both need a real file under XOOPS_ROOT_PATH/modules/<dirname>/, which
 * a unit run has no writable location for. Those need the integration suite.
 */
#[CoversClass(AdminBulkPage::class)]
final class AdminBulkPageLogoUrlTest extends TestCase
{
    #[Test]
    public function nullInfoIsRejected(): void
    {
        self::assertNull(AdminBulkPage::moduleLogoUrl('quotes', null));
    }

    /** @return iterable<string, array{string}> */
    public static function badDirnames(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'dot segment' => ['.'];
        yield 'parent segment' => ['..'];
        yield 'contains a dot' => ['foo.bar'];
        yield 'contains a slash' => ['foo/bar'];
        yield 'contains a backslash' => ['foo\\bar'];
        yield 'contains a space' => ['foo bar'];
        yield 'traversal' => ['../../etc'];
        yield 'null byte' => ["quotes\0"];
        yield 'quote' => ["foo'); alert(1);//"];
    }

    #[Test]
    #[DataProvider('badDirnames')]
    public function dirnameOutsideTheAllowlistIsRejected(string $dirname): void
    {
        self::assertNull(AdminBulkPage::moduleLogoUrl($dirname, $this->info('logo.png')));
    }

    /** @return iterable<string, array{string}> */
    public static function badImageValues(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'absolute posix path' => ['/etc/passwd'];
        yield 'absolute after backslash normalisation' => ['\\windows\\system32\\x.png'];
        yield 'parent traversal' => ['../../../mainfile.php'];
        yield 'parent traversal mid-path' => ['assets/../../secret.png'];
        yield 'dot segment' => ['./logo.png'];
        yield 'empty segment' => ['assets//logo.png'];
        yield 'null byte' => ["logo.png\0.txt"];
        yield 'newline' => ["logo\n.png"];
        yield 'control byte' => ["logo\x1F.png"];
        yield 'protocol' => ['https://evil.example/x.png'];
        yield 'protocol relative' => ['//evil.example/x.png'];
    }

    #[Test]
    #[DataProvider('badImageValues')]
    public function hostileImageValueIsRejected(string $image): void
    {
        self::assertNull(AdminBulkPage::moduleLogoUrl('quotes', $this->info($image)));
    }

    /**
     * A well-formed request for a module that is not on disk still resolves to null.
     *
     * The suffix is random per run, not a fixed literal: the point of the case is
     * "a dirname that certainly does not exist", and a hard-coded name quietly
     * stops testing that the day a fixture or a real module happens to use it.
     */
    #[Test]
    public function absentModuleDirectoryIsRejected(): void
    {
        $absent = 'no_such_module_' . \bin2hex(\random_bytes(16));

        self::assertNull(AdminBulkPage::moduleLogoUrl($absent, $this->info('assets/logo.png')));
    }

    /**
     * @return array{dirname:string,name:string,version:mixed,module_status:mixed,image:string,description:string}
     */
    private function info(string $image): array
    {
        return [
            'dirname' => 'quotes',
            'name' => 'Quotes',
            'version' => '1.0',
            'module_status' => 'Beta',
            'image' => $image,
            'description' => 'test',
        ];
    }
}
