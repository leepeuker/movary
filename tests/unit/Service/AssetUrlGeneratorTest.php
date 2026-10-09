<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use InvalidArgumentException;
use Movary\Service\AssetUrlGenerator;
use Movary\Util\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(AssetUrlGenerator::class)]
class AssetUrlGeneratorTest extends TestCase
{
    public static function provideInvalidPaths() : array
    {
        return [
            [''],
            ['/js/app.js'],
            ['../app.js'],
            ['js/../app.js'],
            ['js//app.js'],
            ['js\\app.js'],
            ['js/app.js?debug=true'],
        ];
    }

    public function testGeneratesContentVersionedUrl() : void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('fileExists')->with('/app/public/js/app.js')->willReturn(true);
        $file->expects(self::once())->method('readFile')->with('/app/public/js/app.js')->willReturn('contents');

        $subject = new AssetUrlGenerator($file, '/app/public/', 'https://example.com/movary/');

        self::assertSame(
            'https://example.com/movary/js/app.js?v=' . substr(hash('sha256', 'contents'), 0, 12),
            $subject->generate('js/app.js'),
        );
    }

    public function testCachesVersionDuringRequest() : void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('fileExists')->willReturn(true);
        $file->expects(self::once())->method('readFile')->willReturn('contents');

        $subject = new AssetUrlGenerator($file, '/app/public', '');

        self::assertSame($subject->generate('css/global.css'), $subject->generate('css/global.css'));
    }

    public function testChangedContentsProduceDifferentUrls() : void
    {
        $firstFile = $this->createStub(File::class);
        $firstFile->method('fileExists')->willReturn(true);
        $firstFile->method('readFile')->willReturn('first');
        $secondFile = $this->createStub(File::class);
        $secondFile->method('fileExists')->willReturn(true);
        $secondFile->method('readFile')->willReturn('second');

        $firstUrl = (new AssetUrlGenerator($firstFile, '/app/public', ''))->generate('js/app.js');
        $secondUrl = (new AssetUrlGenerator($secondFile, '/app/public', ''))->generate('js/app.js');

        self::assertNotSame($firstUrl, $secondUrl);
    }

    public function testRejectsMissingAsset() : void
    {
        $file = $this->createStub(File::class);
        $file->method('fileExists')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset does not exist: js/missing.js');

        (new AssetUrlGenerator($file, '/app/public', ''))->generate('js/missing.js');
    }

    #[DataProvider('provideInvalidPaths')]
    public function testRejectsInvalidPath(string $path) : void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AssetUrlGenerator($this->createStub(File::class), '/app/public', ''))->generate($path);
    }
}
