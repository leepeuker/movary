<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\ValueObject\DateTime;

class Entity
{
    private function __construct(
        private readonly TraktId $traktId,
        private readonly DateTime $lastUpdatedAt,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            TraktId::createFromString($data['trakt_id']),
            DateTime::createFromString($data['lat_updated_at']),
        );
    }

    public function getLastUpdatedAt() : DateTime
    {
        return $this->lastUpdatedAt;
    }

    public function getTraktId() : TraktId
    {
        return $this->traktId;
    }
}
