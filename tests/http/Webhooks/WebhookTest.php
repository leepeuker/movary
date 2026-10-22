<?php declare(strict_types=1);

namespace Tests\Http\Movary\Webhooks;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpResponse;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class WebhookTest extends HttpTestCase
{
    #[DataProvider('providerProvider')]
    public function testRejectsUnknownWebhookId(string $provider) : void
    {
        $this->http
            ->requestRaw('POST', '/webhooks/' . $provider . '/unknown', '')
            ->assertStatus(404)
            ->assertBodySame('');
    }

    public function testRejectsMalformedPlexPayload() : void
    {
        $webhookId = $this->createWebhookId('plex');

        $this->http
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

        $this->http
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

        $this->http
            ->requestRaw('POST', '/webhooks/kodi/' . $webhookId, '{}', ['Content-Type' => 'text/plain'])
            ->assertStatus(415);
    }

    public function testRejectsMalformedEmbyPayload() : void
    {
        $webhookId = $this->createWebhookId('emby');

        $this->http
            ->postForm('/webhooks/emby/' . $webhookId, ['data' => '{'])
            ->assertStatus(400);
    }

    public function testRejectsOversizedEmbyPayload() : void
    {
        $webhookId = $this->createWebhookId('emby');

        $this->http
            ->postForm('/webhooks/emby/' . $webhookId, ['data' => str_repeat(' ', 262145)])
            ->assertStatus(413);
    }

    /** @param callable(self, string): HttpResponse $send */
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

    /** @return array<string, array{string, callable(self, string): HttpResponse}> */
    public static function validUnsupportedEventProvider() : array
    {
        return [
            'Plex unsupported event' => [
                'plex',
                static fn(self $test, string $id) => $test->http->postMultipart(
                    '/webhooks/plex/' . $id,
                    ['payload' => '{"event":"library.new"}'],
                ),
            ],
            'Jellyfin unsupported event' => [
                'jellyfin',
                static fn(self $test, string $id) => $test->http->post(
                    '/webhooks/jellyfin/' . $id,
                    ['NotificationType' => 'PlaybackStart', 'ItemType' => 'Movie'],
                ),
            ],
            'Emby unsupported event' => [
                'emby',
                static fn(self $test, string $id) => $test->http->postForm(
                    '/webhooks/emby/' . $id,
                    ['data' => '{"Event":"playback.start","Item":{"Type":"Movie"}}'],
                ),
            ],
            'Kodi event ignored by disabled setting' => [
                'kodi',
                static fn(self $test, string $id) => $test->http->post(
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
