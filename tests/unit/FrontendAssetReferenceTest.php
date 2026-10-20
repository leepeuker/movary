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
