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
    public function testLocalJavascriptAndStylesheetReferencesUseAssetUrlGenerator() : void
    {
        $templateDirectory = __DIR__ . '/../../templates';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templateDirectory));

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo === false || $file->getExtension() !== 'twig') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertNotFalse($contents);

            preg_match_all('/<(?:script|link)\b[^>]*(?:src|href)="([^"]+)"/', $contents, $matches);
            foreach ($matches[1] as $assetReference) {
                if (str_contains($assetReference, 'js/') === false
                    && str_contains($assetReference, 'css/') === false
                ) {
                    continue;
                }

                self::assertStringContainsString(
                    'asset_url(',
                    $assetReference,
                    sprintf('Unversioned frontend asset in %s', $file->getPathname()),
                );
            }
        }
    }
}
