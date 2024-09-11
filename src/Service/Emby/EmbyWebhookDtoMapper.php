<?php declare(strict_types=1);

namespace Movary\Service\Emby;

use DateTime;
use DateTimeZone;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use RuntimeException;

class EmbyWebhookDtoMapper
{
    private const string SUPPORTED_NOTIFICATION_TYPE = 'playback.stop';

    private const string SUPPORTED_ITEM_TYPE = 'Movie';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function map(array $webhookPayload) : ?EmbyWebhookDto
    {
        $notificationType = $webhookPayload['Event'] ?? null;
        $itemType = $webhookPayload['Item']['Type'] ?? null;

        if ($itemType !== self::SUPPORTED_ITEM_TYPE) {
            $this->logger->debug('Emby: Ignored webhook because item type is not supported', ['itemType' => $itemType]);

            return null;
        }

        if ($notificationType !== self::SUPPORTED_NOTIFICATION_TYPE) {
            $this->logger->debug('Emby: Ignored webhook because notification type is not supported', ['notificationType' => $notificationType]);

            return null;
        }

        return EmbyWebhookDto::create(
            $webhookPayload['Item']['Name'] ?? null,
            empty($webhookPayload['Item']['ProviderIds']['Tmdb']) === true ? null : (int)$webhookPayload['Item']['ProviderIds']['Tmdb'],
            $this->getWatchDate($webhookPayload['Date'] ?? null),
            $webhookPayload['PlaybackInfo']['PlayedToCompletion'],
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
