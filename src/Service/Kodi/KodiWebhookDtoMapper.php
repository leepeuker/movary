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
}
