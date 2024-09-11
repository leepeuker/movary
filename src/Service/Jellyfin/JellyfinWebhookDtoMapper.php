<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use DateTime;
use DateTimeZone;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use RuntimeException;

class JellyfinWebhookDtoMapper
{
    private const string SUPPORTED_NOTIFICATION_TYPE = 'PlaybackStop';

    private const string SUPPORTED_ITEM_TYPE = 'Movie';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function map(array $webhookPayload) : ?JellyfinWebhookDto
    {
        $notificationType = $webhookPayload['NotificationType'] ?? null;
        $itemType = $webhookPayload['ItemType'] ?? null;

        if ($itemType !== self::SUPPORTED_ITEM_TYPE) {
            $this->logger->debug('Jellyfin: Ignored webhook because item type is not supported', ['itemType' => $itemType]);

            return null;
        }

        if ($notificationType !== self::SUPPORTED_NOTIFICATION_TYPE) {
            $this->logger->debug('Jellyfin: Ignored webhook because notification type is not supported', ['notificationType' => $notificationType]);

            return null;
        }

        return JellyfinWebhookDto::create(
            $webhookPayload['Name'] ?? null,
            empty($webhookPayload['Provider_tmdb']) === true ? null : (int)$webhookPayload['Provider_tmdb'],
            $this->getWatchDate($webhookPayload['UtcTimestamp'] ?? null),
            $webhookPayload['PlayedToCompletion'],
        );
    }

    private function getWatchDate(?string $timestamp) : Date
    {
        $timestampWithoutMicroseconds = preg_replace('/\.\d+Z/', '', (string)$timestamp);

        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s', (string)$timestampWithoutMicroseconds, new DateTimeZone('UTC'));
        if ($dateTime === false) {
            throw new RuntimeException('Could not build date time from: ' . $timestamp);
        }

        $dateTime->setTimezone((new DateTime)->getTimezone());

        return Date::createFromString($dateTime->format('Y-m-d'));
    }
}
