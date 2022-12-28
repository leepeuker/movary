<?php

namespace Tests\Unit\Movary\Api\Imdb;

use GuzzleHttp\Client;
use Movary\Api\Imdb\ImdbUrlGenerator;
use Movary\Api\Imdb\ImdbWebScrapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/** @covers \Movary\Api\Imdb\ImdbWebScrapper */
class ImdbWebScrapperTest extends TestCase
{
    private Client|MockObject $httpClientMock;

    private LoggerInterface|MockObject $loggerMock;

    private ImdbWebScrapper $subject;

    private MockObject|ImdbUrlGenerator $urlGeneratorMock;

    public function provideFindRatingData() : array
    {
        return [
            [
                'sc-7ab21ed2-1 jGRxWM">8.3< sc-7ab21ed2-3 dPVcnq">321<',
                [
                    'average' => 8.3,
                    'voteCount' => 321,
                ],
            ],
            [
                'sc-7ab21ed2-1 jGRxWM">8.3< sc-7ab21ed2-3 dPVcnq">321K<',
                [
                    'average' => 8.3,
                    'voteCount' => 321000,
                ],
            ],
            [
                'sc-7ab21ed2-1 jGRxWM">8.3< sc-7ab21ed2-3 dPVcnq">3.2K<',
                [
                    'average' => 8.3,
                    'voteCount' => 3200,
                ],
            ],
            [
                'sc-7ab21ed2-1 jGRxWM">8.3< sc-7ab21ed2-3 dPVcnq">3.2M<',
                [
                    'average' => 8.3,
                    'voteCount' => 3200000,
                ],
            ],
            [
                'sc-7ab21ed2-1 jGRxWM">8.3< sc-7ab21ed2-3 dPVcnq">32M<',
                [
                    'average' => 8.3,
                    'voteCount' => 32000000,
                ],
            ],
            [
                'sc-7ab21ed2-1 jGRxWM">< sc-7ab21ed2-3 dPVcnq"><',
                [
                    'average' => null,
                    'voteCount' => null,
                ],
            ],
        ];
    }

    public function setUp() : void
    {
        $this->httpClientMock = $this->createMock(Client::class);
        $this->urlGeneratorMock = $this->createMock(ImdbUrlGenerator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->subject = new ImdbWebScrapper($this->httpClientMock, $this->urlGeneratorMock, $this->loggerMock);
    }

    /**
     * @dataProvider provideFindRatingData
     */
    public function testFindRating(string $responseContent, array $expectedResult) : void
    {
        $imdbId = 'imdb-id';

        /** @var ResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        /** @var StreamInterface|MockObject $streamInterfaceMock */
        $streamInterfaceMock = $this->createMock(StreamInterface::class);

        $responseMock->expects(self::once())->method('getBody')->willReturn($streamInterfaceMock);
        $streamInterfaceMock->expects(self::once())->method('getContents')->willReturn($responseContent);

        $this->httpClientMock->expects(self::once())->method('get')->willReturn($responseMock);

        self::assertSame(
            $expectedResult,
            $this->subject->findRating($imdbId),
        );
    }
}