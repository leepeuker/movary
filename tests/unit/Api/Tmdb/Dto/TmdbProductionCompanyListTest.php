<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Dto;

use Movary\Api\Tmdb\Dto\TmdbProductionCompany;
use Movary\Api\Tmdb\Dto\TmdbProductionCompanyList;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Api\Tmdb\Dto\TmdbProductionCompanyList */
class TmdbProductionCompanyListTest extends TestCase
{
    public function testCreateFromArray() : void
    {
        $testData = [
            'id' => 120,
            'name' => 'company',
            'origin_country' => 'US',
        ];

        $subject = TmdbProductionCompanyList::createFromArray([$testData]);

        $expectedProductionCompany = TmdbProductionCompany::createFromArray($testData);

        self::assertCount(1, $subject);
        self::assertEquals($expectedProductionCompany, $subject->asArray()[0]);
    }
}
