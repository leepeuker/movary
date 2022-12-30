<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Letterboxd;

use GuzzleHttp\Client;
use Movary\Api\Letterboxd\LetterboxdWebScrapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/** @covers \Movary\Api\Letterboxd\LetterboxdWebScrapper */
class LetterboxdWebScrapperTest extends TestCase
{
    private Client|MockObject $httpClientMock;

    private LetterboxdWebScrapper $subject;

    public function provideTestScrapeLetterboxIdByDiaryUriData() : array
    {
        return [
            [
                'analytic_params[\'film_id\'] = \'kjMW\';',
                'kjMW',
            ],
            [
                'analytic_params[\'film_id\'] = \'9AG0\';',
                '9AG0',
            ],
        ];
    }

    public function setUp() : void
    {
        $this->httpClientMock = $this->createMock(Client::class);

        $this->subject = new LetterboxdWebScrapper($this->httpClientMock);
    }

    /** @dataProvider provideTestScrapeLetterboxIdByDiaryUriData */
    public function testScrapeLetterboxIdByDiaryUri(string $responseContent, string $letterboxId) : void
    {
        $diaryUri = 'uri';

        /** @var ResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        /** @var StreamInterface|MockObject $streamInterfaceMock */
        $streamInterfaceMock = $this->createMock(StreamInterface::class);

        $responseMock->expects(self::once())->method('getBody')->willReturn($streamInterfaceMock);
        $streamInterfaceMock->expects(self::once())->method('getContents')->willReturn($responseContent);

        $this->httpClientMock->expects(self::once())->method('get')->with($diaryUri)->willReturn($responseMock);

        self::assertSame(
            $letterboxId,
            $this->subject->scrapeLetterboxIdByDiaryUri($diaryUri),
        );
    }

    public function testScrapeLetterboxIdByDiaryUriThrowsRuntimeExceptionIfIdCouldNotBeFound() : void
    {
        $diaryUri = 'uri';
        $responseContent = '';

        /** @var ResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        /** @var StreamInterface|MockObject $streamInterfaceMock */
        $streamInterfaceMock = $this->createMock(StreamInterface::class);

        $responseMock->expects(self::once())->method('getBody')->willReturn($streamInterfaceMock);
        $streamInterfaceMock->expects(self::once())->method('getContents')->willReturn($responseContent);

        $this->httpClientMock->expects(self::once())->method('get')->with($diaryUri)->willReturn($responseMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find letterboxd id on page: uri');

        $this->subject->scrapeLetterboxIdByDiaryUri($diaryUri);
    }
}
