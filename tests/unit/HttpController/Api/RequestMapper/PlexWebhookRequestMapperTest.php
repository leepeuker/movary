<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\PlexWebhookRequestMapper;
use Movary\HttpController\Api\RequestMapper\WebhookRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlexWebhookRequestMapper::class)]
class PlexWebhookRequestMapperTest extends TestCase
{
    public function testMapsValidScrobble() : void
    {
        $payload = [
            'event' => 'media.scrobble',
            'user' => true,
            'Metadata' => [
                'librarySectionType' => 'movie',
                'Guid' => [['id' => 'tmdb://42']],
                'lastViewedAt' => 1660000000,
            ],
        ];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testAllowsUnsupportedEventWithoutMovieFields() : void
    {
        $payload = ['event' => 'library.new'];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testRejectsSupportedEventWithoutMetadata() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('Metadata', 'is required and must be an object'),
        );

        $this->createSubject(['event' => 'media.scrobble', 'user' => true])
            ->mapRequest($this->createStub(Request::class));
    }

    public function testRejectsInvalidRating() : void
    {
        $payload = [
            'event' => 'media.rate',
            'user' => true,
            'rating' => 11,
            'Metadata' => [
                'librarySectionType' => 'movie',
                'Guid' => [['id' => 'tmdb://42']],
            ],
        ];
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('rating', 'must be an integer between 1 and 10'),
        );

        $this->createSubject($payload)->mapRequest($this->createStub(Request::class));
    }

    private function createSubject(array $payload) : PlexWebhookRequestMapper
    {
        $requestMapper = $this->createStub(WebhookRequestMapper::class);
        $requestMapper->method('mapMultipartJsonField')->willReturn($payload);

        return new PlexWebhookRequestMapper($requestMapper);
    }
}
