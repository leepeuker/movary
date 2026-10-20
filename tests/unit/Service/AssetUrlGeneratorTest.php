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
    public static function provideAssetDirectoryGenerators() : array
    {
        return [
            ['javascript', 'app.js', 'js/app.js'],
            ['stylesheet', 'global.css', 'css/global.css'],
            ['vendor', 'bootstrap/bootstrap.min.css', 'vendor/bootstrap/bootstrap.min.css'],
        ];
    }

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
            $subject->generateJavascript('app.js'),
        );
    }

    public function testCachesVersionDuringRequest() : void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('fileExists')->willReturn(true);
        $file->expects(self::once())->method('readFile')->willReturn('contents');

        $subject = new AssetUrlGenerator($file, '/app/public', '');

        self::assertSame(
            $subject->generateStylesheet('global.css'),
            $subject->generateStylesheet('global.css'),
        );
    }

    #[DataProvider('provideAssetDirectoryGenerators')]
    public function testGeneratesUrlForAssetDirectory(
        string $assetType,
        string $relativePath,
        string $expectedPath,
    ) : void {
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('fileExists')->with('/app/public/' . $expectedPath)->willReturn(true);
        $file->expects(self::once())->method('readFile')->with('/app/public/' . $expectedPath)->willReturn('contents');

        $subject = new AssetUrlGenerator($file, '/app/public', '');
        $url = match ($assetType) {
            'javascript' => $subject->generateJavascript($relativePath),
            'stylesheet' => $subject->generateStylesheet($relativePath),
            'vendor' => $subject->generateVendor($relativePath),
            default => throw new InvalidArgumentException('Unknown asset type: ' . $assetType),
        };

        self::assertSame(
            '/' . $expectedPath . '?v=' . substr(hash('sha256', 'contents'), 0, 12),
            $url,
        );
    }

    public function testChangedContentsProduceDifferentUrls() : void
    {
        $firstFile = $this->createStub(File::class);
        $firstFile->method('fileExists')->willReturn(true);
        $firstFile->method('readFile')->willReturn('first');
        $secondFile = $this->createStub(File::class);
        $secondFile->method('fileExists')->willReturn(true);
        $secondFile->method('readFile')->willReturn('second');

        $firstUrl = (new AssetUrlGenerator($firstFile, '/app/public', ''))->generateJavascript('app.js');
        $secondUrl = (new AssetUrlGenerator($secondFile, '/app/public', ''))->generateJavascript('app.js');

        self::assertNotSame($firstUrl, $secondUrl);
    }

    public function testRejectsMissingAsset() : void
    {
        $file = $this->createStub(File::class);
        $file->method('fileExists')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset does not exist: js/missing.js');

        (new AssetUrlGenerator($file, '/app/public', ''))->generateJavascript('missing.js');
    }

    #[DataProvider('provideInvalidPaths')]
    public function testRejectsInvalidPath(string $path) : void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AssetUrlGenerator($this->createStub(File::class), '/app/public', ''))->generateJavascript($path);
    }

    public function testAssetDirectoryGeneratorRejectsPathTraversal() : void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AssetUrlGenerator($this->createStub(File::class), '/app/public', ''))
            ->generateVendor('../js/app.js');
    }
}
