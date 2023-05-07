<?php declare(strict_types=1);

namespace Movary\Service\Emby;

use Movary\ValueObject\Date;

class EmbyWebhookDto
{
    private function __construct(
        private readonly ?string $movieName,
        private readonly ?int $tmdbId,
        private readonly Date $watchDate,
        private readonly bool $playedToCompletion,
    ) {
    }

    public static function create(?string $movieName, ?int $tmdbId, Date $watchDate, bool $playedToCompletion) : self
    {
        return new self($movieName, $tmdbId, $watchDate, $playedToCompletion);
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

    public function isPlayedToCompletion() : bool
    {
        return $this->playedToCompletion;
    }
}
