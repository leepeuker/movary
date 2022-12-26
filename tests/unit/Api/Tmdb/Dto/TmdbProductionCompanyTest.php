<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Dto;

use Movary\Api\Tmdb\Dto\TmdbProductionCompany;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Api\Tmdb\Dto\TmdbProductionCompany */
class TmdbProductionCompanyTest extends TestCase
{
    public function testCreateFromArray() : void
    {
        $subject = TmdbProductionCompany::createFromArray(['id' => 12, 'name' => 'Company Name', 'origin_country' => 'US']);

        self::assertSame(12, $subject->getId());
        self::assertSame('Company Name', $subject->getName());
        self::assertSame('US', $subject->getOriginCountry());
    }
}
