<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\JellyfinWebhookRequestMapper;
use Movary\HttpController\Api\RequestMapper\WebhookRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JellyfinWebhookRequestMapper::class)]
class JellyfinWebhookRequestMapperTest extends TestCase
{
    public function testMapsValidPlaybackStop() : void
    {
        $payload = [
            'NotificationType' => 'PlaybackStop',
            'ItemType' => 'Movie',
            'PlayedToCompletion' => true,
            'UtcTimestamp' => '2026-10-21T12:34:56.123Z',
            'Provider_tmdb' => '42',
        ];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testAllowsUnsupportedEventWithoutPlaybackFields() : void
    {
        $payload = ['NotificationType' => 'PlaybackStart', 'ItemType' => 'Movie'];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testRejectsMissingNotificationType() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('NotificationType', 'is required and must be a string'),
        );

        $this->createSubject(['ItemType' => 'Movie'])->mapRequest($this->createStub(Request::class));
    }

    public function testRejectsInvalidTimestamp() : void
    {
        $payload = [
            'NotificationType' => 'PlaybackStop',
            'ItemType' => 'Movie',
            'PlayedToCompletion' => true,
            'UtcTimestamp' => 'not-a-date',
        ];
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('UtcTimestamp', 'must be a valid ISO 8601 timestamp'),
        );

        $this->createSubject($payload)->mapRequest($this->createStub(Request::class));
    }

    private function createSubject(array $payload) : JellyfinWebhookRequestMapper
    {
        $requestMapper = $this->createStub(WebhookRequestMapper::class);
        $requestMapper->method('mapJsonBody')->willReturn($payload);

        return new JellyfinWebhookRequestMapper($requestMapper);
    }
}
