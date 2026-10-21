<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\EmbyWebhookRequestMapper;
use Movary\HttpController\Api\RequestMapper\WebhookRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmbyWebhookRequestMapper::class)]
class EmbyWebhookRequestMapperTest extends TestCase
{
    public function testMapsValidPlaybackStop() : void
    {
        $payload = [
            'Event' => 'playback.stop',
            'Date' => '2026-10-21T12:34:56.123Z',
            'Item' => ['Type' => 'Movie', 'ProviderIds' => ['Tmdb' => '42']],
            'PlaybackInfo' => ['PlayedToCompletion' => true],
        ];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testAllowsUnsupportedEventWithoutPlaybackFields() : void
    {
        $payload = ['Event' => 'playback.start', 'Item' => ['Type' => 'Movie']];

        self::assertSame($payload, $this->createSubject($payload)->mapRequest($this->createStub(Request::class)));
    }

    public function testRejectsMissingItemDiscriminator() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('Item.Type', 'is required and must be a string'),
        );

        $this->createSubject(['Event' => 'playback.stop', 'Item' => []])
            ->mapRequest($this->createStub(Request::class));
    }

    public function testRejectsInvalidPlayedToCompletion() : void
    {
        $payload = [
            'Event' => 'playback.stop',
            'Date' => '2026-10-21T12:34:56.123Z',
            'Item' => ['Type' => 'Movie'],
            'PlaybackInfo' => ['PlayedToCompletion' => 'yes'],
        ];
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField(
                'PlaybackInfo.PlayedToCompletion',
                'is required and must be a boolean',
            ),
        );

        $this->createSubject($payload)->mapRequest($this->createStub(Request::class));
    }

    private function createSubject(array $payload) : EmbyWebhookRequestMapper
    {
        $requestMapper = $this->createStub(WebhookRequestMapper::class);
        $requestMapper->method('mapJsonField')->willReturn($payload);

        return new EmbyWebhookRequestMapper($requestMapper);
    }
}
