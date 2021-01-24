<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Rating;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\DateTime;

class Entity
{
    private DateTime $ratedAt;

    private int $rating;

    private TraktId $traktId;

    private function __construct(TraktId $traktId, int $rating, DateTime $ratedAt)
    {
        $this->traktId = $traktId;
        $this->rating = $rating;
        $this->ratedAt = $ratedAt;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            TraktId::createFromString($data['trakt_id']),
            (int)$data['rating'],
            DateTime::createFromString($data['rated_at']),
        );
    }

    public function getRatedAt() : DateTime
    {
        return $this->ratedAt;
    }

    public function getRating() : int
    {
        return $this->rating;
    }

    public function getTraktId() : TraktId
    {
        return $this->traktId;
    }
}
