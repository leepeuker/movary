<?php

namespace Tests\Unit\Movary\Api\Imdb;

use GuzzleHttp\Client;
use Movary\Api\Imdb\ImdbUrlGenerator;
use Movary\Api\Imdb\ImdbWebScrapper;
use Movary\ValueObject\ImdbRating;
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
                'iZlgcd">7.9</span>
                bjjENQ">229.240</div>',
                ImdbRating::create(7.9, 229240)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">229,240</div>',
                ImdbRating::create(7.9, 229240)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">229240</div>',
                ImdbRating::create(7.9, 229240)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">1.229,240</div>',
                ImdbRating::create(7.9, 1229240)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">40</div>',
                ImdbRating::create(7.9, 40)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">40K</div>',
                ImdbRating::create(7.9, 40000)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">4.1K</div>',
                ImdbRating::create(7.9, 4100)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">14.12K</div>',
                ImdbRating::create(7.9, 14120)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">10M</div>',
                ImdbRating::create(7.9, 10000000)
            ],
            [
                'iZlgcd">7,9</span>
                bjjENQ">10.1M</div>',
                ImdbRating::create(7.9, 10100000)
            ],
            [
                'iZlgcd">7,9</span>',
                null
            ],
            [
                '',
                null
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
    public function testFindRating(string $responseContent, ?ImdbRating $expectedResult) : void
    {
        $imdbId = 'imdb-id';

        /** @var ResponseInterface|MockObject $responseMock */
        $responseMock = $this->createMock(ResponseInterface::class);
        /** @var StreamInterface|MockObject $streamInterfaceMock */
        $streamInterfaceMock = $this->createMock(StreamInterface::class);

        $responseMock->expects(self::once())->method('getStatusCode')->willReturn(200);
        $responseMock->expects(self::once())->method('getBody')->willReturn($streamInterfaceMock);
        $streamInterfaceMock->expects(self::once())->method('getContents')->willReturn($responseContent);

        $this->httpClientMock->expects(self::once())->method('get')->willReturn($responseMock);

        self::assertEquals(
            $expectedResult,
            $this->subject->findRating($imdbId),
        );
    }
}
