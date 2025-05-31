<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Company;

use Movary\Domain\Company\CompanyEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Domain\Company\CompanyEntity::class)]
class CompanyEntityTest extends TestCase
{
    public static function testCreateFromArrayWithAllPossibleValuesSetToNull() : void
    {
        $subject = CompanyEntity::createFromArray(
            [
                'id' => 12,
                'name' => 'Company Name',
                'origin_country' => null,
                'tmdb_id' => 100,
            ],
        );

        self::assertSame(12, $subject->getId());
        self::assertSame('Company Name', $subject->getName());
        self::assertSame(100, $subject->getTmdbId());

        self::assertNull($subject->getOriginCountry());
    }

    public static function testCreateFromArrayWithAllValuesSet() : void
    {
        $subject = CompanyEntity::createFromArray(
            [
                'id' => 12,
                'name' => 'Company Name',
                'origin_country' => 'US',
                'tmdb_id' => 100,
            ],
        );

        self::assertSame(12, $subject->getId());
        self::assertSame('Company Name', $subject->getName());
        self::assertSame(100, $subject->getTmdbId());

        self::assertSame('US', $subject->getOriginCountry());
    }
}
