<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Company;

use Movary\Domain\Company\CompanyApi;
use Movary\Domain\Company\CompanyEntity;
use Movary\Domain\Company\CompanyRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Domain\Company\CompanyApi */
class CompanyApiTest extends TestCase
{
    private const int ID = 12;

    private const string NAME = 'Company Name';

    private const string ORIGIN_COUNTRY = 'US';

    private const int TMDB_ID = 100;

    private MockObject|CompanyRepository $repositoryMock;

    private CompanyApi $subject;

    public function setUp() : void
    {
        $this->repositoryMock = $this->createMock(CompanyRepository::class);

        $this->subject = new CompanyApi($this->repositoryMock);
    }

    public function testCreate() : void
    {
        $companyData = $this->generateCompanyData();

        $this->repositoryMock
            ->expects(self::once())
            ->method('create')
            ->with(self::NAME, self::ORIGIN_COUNTRY, self::TMDB_ID)
            ->willReturn($companyData);

        self::assertEquals(
            CompanyEntity::createFromArray($companyData),
            $this->subject->create(self::NAME, self::ORIGIN_COUNTRY, self::TMDB_ID),
        );
    }

    public function testDelete() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('delete')
            ->with(self::ID);

        $this->subject->deleteByTmdbId(self::ID);
    }

    public function testFindByNameAndOriginCountry() : void
    {
        $companyData = $this->generateCompanyData();

        $this->repositoryMock
            ->expects(self::once())
            ->method('findByNameAndOriginCountry')
            ->with(self::NAME, self::ORIGIN_COUNTRY)
            ->willReturn($companyData);

        self::assertEquals(
            CompanyEntity::createFromArray($companyData),
            $this->subject->findByNameAndOriginCountry(self::NAME, self::ORIGIN_COUNTRY),
        );
    }

    public function testFindByNameAndOriginCountryReturnsNullIfNothingWasFound() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findByNameAndOriginCountry')
            ->with(self::NAME, self::ORIGIN_COUNTRY)
            ->willReturn(null);

        self::assertNull($this->subject->findByNameAndOriginCountry(self::NAME, self::ORIGIN_COUNTRY));
    }

    public function testFindByTmdbId() : void
    {
        $companyData = $this->generateCompanyData();

        $this->repositoryMock
            ->expects(self::once())
            ->method('findByTmdbId')
            ->with(self::TMDB_ID)
            ->willReturn($companyData);

        self::assertEquals(
            CompanyEntity::createFromArray($companyData),
            $this->subject->findByTmdbId(self::TMDB_ID),
        );
    }

    public function testFindByTmdbIdReturnsNullIfNothingWasFound() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findByTmdbId')
            ->with(self::TMDB_ID)
            ->willReturn(null);

        self::assertNull($this->subject->findByTmdbId(self::TMDB_ID));
    }

    public function testUpdate() : void
    {
        $companyData = $this->generateCompanyData();

        $this->repositoryMock
            ->expects(self::once())
            ->method('update')
            ->with(self::ID, self::NAME, self::ORIGIN_COUNTRY)
            ->willReturn($companyData);

        self::assertEquals(
            CompanyEntity::createFromArray($companyData),
            $this->subject->update(self::ID, self::NAME, self::ORIGIN_COUNTRY),
        );
    }

    private function generateCompanyData() : array
    {
        return [
            'id' => self::ID,
            'name' => self::NAME,
            'origin_country' => self::ORIGIN_COUNTRY,
            'tmdb_id' => self::TMDB_ID,
        ];
    }
}
