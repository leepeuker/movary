<?php declare(strict_types=1);

namespace Movary\Service\Kodi;

use Movary\ValueObject\Date;

class KodiWebhookDto
{
    private function __construct(
        private readonly ?string $movieName,
        private readonly ?int $tmdbId,
        private readonly Date $watchDate,
    ) {
    }

    public static function create(?string $movieName, ?int $tmdbId, Date $watchDate) : self
    {
        return new self($movieName, $tmdbId, $watchDate);
    }

    public function getMovieName() : ?string
    {
        return $this->movieName;
    }

    public function getTmdbId() : ?int
    {
        return $this->tmdbId;
    }

    public function getWatchDate() : Date
    {
        return $this->watchDate;
    }
}
