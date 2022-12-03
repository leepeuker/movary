<?php declare(strict_types=1);

namespace Movary\Application\Movie\History;

use Movary\ValueObject\Date;

class MovieHistoryEntity
{
    private function __construct(
        private readonly int $id,
        private readonly Date $watchedAt
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            Date::createFromString($data['watched_at'])
        );
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getWatchedAt() : Date
    {
        return $this->watchedAt;
    }
}
