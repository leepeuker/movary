<?php declare(strict_types=1);

namespace Movary\Service\Kodi;

use DateTime;
use DateTimeZone;
use Movary\ValueObject\Date;
use RuntimeException;

class KodiWebhookDtoMapper
{
    public function __construct()
    {
    }

    public function map(array $webhookPayload) : ?KodiWebhookDto
    {
        return KodiWebhookDto::create(
            $webhookPayload['title'] ?? null,
            empty($webhookPayload['uniqueIds']['tmdbId']) === true ? null : (int)$webhookPayload['uniqueIds']['tmdbId'],
            Date::create(),
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
