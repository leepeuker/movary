<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Company;

use Movary\Domain\Company\CompanyEntity;
use Movary\Domain\Company\CompanyEntityList;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Domain\Company\CompanyEntity */
class CompanyEntityListTest extends TestCase
{
    public function testAdd() : void
    {
        $companyMock = $this->createMock(CompanyEntity::class);

        $subject = CompanyEntityList::create();
        self::assertCount(0, $subject);

        $subject->add($companyMock);
        self::assertCount(1, $subject);
        self::assertSame($companyMock, $subject->asArray()[0]);
    }

    public function testCreateFromArray() : void
    {
        $companyData = [
            'id' => 12,
            'name' => 'Company Name',
            'origin_country' => 'US',
            'tmdb_id' => 100,
        ];

        $subject = CompanyEntityList::createFromArray([$companyData]);

        self::assertCount(1, $subject);
        self::assertEquals(CompanyEntity::createFromArray($companyData), $subject->asArray()[0]);
    }
}
