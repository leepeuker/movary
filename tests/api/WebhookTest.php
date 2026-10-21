<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Api\Movary\Support\ApiResponse;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class WebhookTest extends ApiTestCase
{
    #[DataProvider('providerProvider')]
    public function testRejectsUnknownWebhookId(string $provider) : void
    {
        $this->api
            ->requestRaw('POST', '/webhooks/' . $provider . '/unknown', '')
            ->assertStatus(404)
            ->assertBodySame('');
    }

    public function testRejectsMalformedPlexPayload() : void
    {
        $webhookId = $this->createWebhookId('plex');

        $this->api
            ->postMultipart('/webhooks/plex/' . $webhookId, ['payload' => '{'])
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Webhook payload must contain valid JSON.',
            ]);
    }

    public function testRejectsMalformedJellyfinPayload() : void
    {
        $webhookId = $this->createWebhookId('jellyfin');

        $this->api
            ->requestRaw(
                'POST',
                '/webhooks/jellyfin/' . $webhookId,
                '{',
                ['Content-Type' => 'application/json'],
            )
            ->assertStatus(400);
    }

    public function testRejectsWrongKodiContentType() : void
    {
        $webhookId = $this->createWebhookId('kodi');

        $this->api
            ->requestRaw('POST', '/webhooks/kodi/' . $webhookId, '{}', ['Content-Type' => 'text/plain'])
            ->assertStatus(415);
    }

    public function testRejectsMalformedEmbyPayload() : void
    {
        $webhookId = $this->createWebhookId('emby');

        $this->api
            ->postForm('/webhooks/emby/' . $webhookId, ['data' => '{'])
            ->assertStatus(400);
    }

    public function testRejectsOversizedEmbyPayload() : void
    {
        $webhookId = $this->createWebhookId('emby');

        $this->api
            ->postForm('/webhooks/emby/' . $webhookId, ['data' => str_repeat(' ', 262145)])
            ->assertStatus(413);
    }

    /** @param callable(self, string): ApiResponse $send */
    #[DataProvider('validUnsupportedEventProvider')]
    public function testAcceptsValidEvent(string $provider, callable $send) : void
    {
        $webhookId = $this->createWebhookId($provider);

        $send($this, $webhookId)->assertStatus(200)->assertBodySame('');
    }

    /** @return array<string, array{string}> */
    public static function providerProvider() : array
    {
        return [
            'Plex' => ['plex'],
            'Jellyfin' => ['jellyfin'],
            'Emby' => ['emby'],
            'Kodi' => ['kodi'],
        ];
    }

    /** @return array<string, array{string, callable(self, string): ApiResponse}> */
    public static function validUnsupportedEventProvider() : array
    {
        return [
            'Plex unsupported event' => [
                'plex',
                static fn(self $test, string $id) => $test->api->postMultipart(
                    '/webhooks/plex/' . $id,
                    ['payload' => '{"event":"library.new"}'],
                ),
            ],
            'Jellyfin unsupported event' => [
                'jellyfin',
                static fn(self $test, string $id) => $test->api->post(
                    '/webhooks/jellyfin/' . $id,
                    ['NotificationType' => 'PlaybackStart', 'ItemType' => 'Movie'],
                ),
            ],
            'Emby unsupported event' => [
                'emby',
                static fn(self $test, string $id) => $test->api->postForm(
                    '/webhooks/emby/' . $id,
                    ['data' => '{"Event":"playback.start","Item":{"Type":"Movie"}}'],
                ),
            ],
            'Kodi event ignored by disabled setting' => [
                'kodi',
                static fn(self $test, string $id) => $test->api->post(
                    '/webhooks/kodi/' . $id,
                    ['title' => 'Movie', 'uniqueIds' => []],
                ),
            ],
        ];
    }

    private function createWebhookId(string $provider) : string
    {
        $fixture = $this->fixture->createUserWithToken();

        return $this->fixture->createWebhookId($fixture['user']->getId(), $provider);
    }
}
