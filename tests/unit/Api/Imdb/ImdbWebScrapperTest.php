<?php

namespace Tests\Unit\Movary\Api\Imdb;

use GuzzleHttp\Client;
use Movary\Api\Imdb\ImdbUrlGenerator;
use Movary\Api\Imdb\ImdbWebScrapper;
use Movary\Api\Imdb\ValueObject\ImdbRating;
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
                '    <div class="allText">
            <div class="allText">
                229.240
IMDb users have given a <a href="https://help.imdb.com/article/imdb/track-movies-tv/weighted-average-ratings/GWT2DSBYVT2F25SK?ref_=cons_tt_rt_wtavg">weighted average</a> vote of                 7.9 / 10



            <br /><br />',
                ImdbRating::create(7.9, 229240)
            ],
            [
                '    <div class="allText">
            <div class="allText">
                229,240
IMDb users have given a <a href="https://help.imdb.com/article/imdb/track-movies-tv/weighted-average-ratings/GWT2DSBYVT2F25SK?ref_=cons_tt_rt_wtavg">weighted average</a> vote of                 7,9 / 10



            <br /><br />',
                ImdbRating::create(7.9, 229240)
            ],
            [
                '    <div class="allText">
            <div class="allText">
                1.229,240
IMDb users have given a <a href="https://help.imdb.com/article/imdb/track-movies-tv/weighted-average-ratings/GWT2DSBYVT2F25SK?ref_=cons_tt_rt_wtavg">weighted average</a> vote of                 7,9 / 10



            <br /><br />',
                ImdbRating::create(7.9, 1229240)
            ],
            [
                '    <div class="allText">
            <div class="allText">
                40
IMDb users have given a <a href="https://help.imdb.com/article/imdb/track-movies-tv/weighted-average-ratings/GWT2DSBYVT2F25SK?ref_=cons_tt_rt_wtavg">weighted average</a> vote of                 7,9 / 10



            <br /><br />',
                ImdbRating::create(7.9, 40)
            ],
            [
                '    <div class="allText">
            <div class="allText">
                
IMDb users have given a <a href="https://help.imdb.com/article/imdb/track-movies-tv/weighted-average-ratings/GWT2DSBYVT2F25SK?ref_=cons_tt_rt_wtavg">weighted average</a> vote of                 7,9 / 10



            <br /><br />',
                null
            ],
            [
                '    <div class="allText">
            <div class="allText">
                
IMDb users have given a <a href="https://help.imdb.com/article/imdb/track-movies-tv/weighted-average-ratings/GWT2DSBYVT2F25SK?ref_=cons_tt_rt_wtavg">weighted average</a> vote of                  / 10



            <br /><br />',
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
