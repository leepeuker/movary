<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Service\MovieTitleFormatter;
use Movary\ValueObject\MovieTitleDisplay;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieTitleFormatter::class)]
class MovieTitleFormatterTest extends TestCase
{
    public static function provideTitles() : array
    {
        return [
            'localized' => ['localized', 'Spirited Away', 'Sen to Chihiro no Kamikakushi', 'Spirited Away'],
            'original' => ['original', 'Spirited Away', 'Sen to Chihiro no Kamikakushi', 'Sen to Chihiro no Kamikakushi'],
            'both' => ['both', 'Spirited Away', 'Sen to Chihiro no Kamikakushi', 'Spirited Away (Sen to Chihiro no Kamikakushi)'],
            'missing original' => ['original', 'Spirited Away', null, 'Spirited Away'],
            'empty original' => ['original', 'Spirited Away', '', 'Spirited Away'],
            'identical titles' => ['both', 'Alien', 'Alien', 'Alien'],
            'missing localized title' => ['localized', '', '千と千尋の神隠し', '千と千尋の神隠し'],
        ];
    }

    #[DataProvider('provideTitles')]
    public function testFormatsTitle(
        string $display,
        string $localizedTitle,
        ?string $originalTitle,
        string $expected,
    ) : void {
        self::assertSame(
            $expected,
            (new MovieTitleFormatter())->format(
                $localizedTitle,
                $originalTitle,
                MovieTitleDisplay::createFromString($display),
            ),
        );
    }
}
