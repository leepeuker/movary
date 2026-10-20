<?php declare(strict_types=1);

namespace Tests\Unit\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

#[CoversNothing]
class FrontendAssetReferenceTest extends TestCase
{
    public function testBaseTemplateIncludesMobileAppMetadata() : void
    {
        $templateContents = file_get_contents(__DIR__ . '/../../templates/base.html.twig');
        self::assertNotFalse($templateContents);

        self::assertStringContainsString('rel="apple-touch-icon"', $templateContents);
        self::assertStringContainsString('/manifest.webmanifest', $templateContents);
        self::assertStringContainsString('name="apple-mobile-web-app-capable" content="yes"', $templateContents);
        self::assertStringContainsString(
            'name="apple-mobile-web-app-title" content="{{ applicationName }}"',
            $templateContents,
        );
        self::assertStringContainsString("theme == 'dark'", $templateContents);
    }

    public function testPwaManifestIncludesPlatformMetadata() : void
    {
        $manifestContents = file_get_contents(__DIR__ . '/../../public/manifest.json');
        self::assertNotFalse($manifestContents);

        $manifest = json_decode($manifestContents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('./', $manifest['id']);
        self::assertSame('./', $manifest['scope']);
        self::assertIsArray($manifest['icons']);

        $maskableIcons = array_filter(
            $manifest['icons'],
            static fn (mixed $icon) : bool => is_array($icon)
                && isset($icon['purpose'])
                && is_string($icon['purpose'])
                && in_array('maskable', explode(' ', $icon['purpose']), true),
        );
        self::assertNotEmpty($maskableIcons);
    }

    public function testPwaManifestIconSizesMatchFiles() : void
    {
        $manifestContents = file_get_contents(__DIR__ . '/../../public/manifest.json');
        self::assertNotFalse($manifestContents);

        $manifest = json_decode($manifestContents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['icons']);

        foreach ($manifest['icons'] as $icon) {
            self::assertIsArray($icon);
            self::assertIsString($icon['src']);
            self::assertIsString($icon['sizes']);
            $imageContents = file_get_contents(__DIR__ . '/../../public/' . $icon['src']);
            self::assertNotFalse($imageContents);

            $dimensions = unpack('Nwidth/Nheight', substr($imageContents, 16, 8));
            self::assertNotFalse($dimensions);
            self::assertSame($dimensions['width'] . 'x' . $dimensions['height'], $icon['sizes']);
        }
    }

    public function testPwaManifestUrlsAreRelativeToApplicationPath() : void
    {
        $manifestContents = file_get_contents(__DIR__ . '/../../public/manifest.json');
        self::assertNotFalse($manifestContents);

        $manifest = json_decode($manifestContents, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('./', $manifest['start_url']);

        self::assertIsArray($manifest['icons']);
        foreach ($manifest['icons'] as $icon) {
            self::assertIsArray($icon);
            self::assertIsString($icon['src']);
            self::assertStringStartsNotWith('/', $icon['src']);
        }
    }

    public function testLocalJavascriptAndStylesheetReferencesUseDirectorySpecificUrlGenerator() : void
    {
        $templateDirectory = __DIR__ . '/../../templates';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templateDirectory));

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo === false || $file->getExtension() !== 'twig') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertNotFalse($contents);

            preg_match_all('/<(script|link)\b[^>]*(?:src|href)="([^"]+)"/', $contents, $matches);
            foreach ($matches[2] as $index => $assetReference) {
                if (str_contains($assetReference, 'applicationUrl')
                    || preg_match('/^(?:https?:)?\/\//', $assetReference) === 1
                ) {
                    continue;
                }

                $expectedFunctions = $matches[1][$index] === 'script'
                    ? ['js_url(', 'vendor_url(']
                    : ['css_url(', 'vendor_url('];

                self::assertTrue(
                    str_contains($assetReference, $expectedFunctions[0])
                        || str_contains($assetReference, $expectedFunctions[1]),
                    sprintf('Frontend asset uses the wrong URL generator in %s', $file->getPathname()),
                );
            }
        }
    }
}
