<?php declare(strict_types=1);

namespace Movary\Application\Movie\History;

use Movary\ValueObject\DateTime;

class Entity
{
    private int $id;

    private DateTime $watchedAt;

    private function __construct(int $id, DateTime $watchedAt)
    {
        $this->id = $id;
        $this->watchedAt = $watchedAt;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            DateTime::createFromString($data['watched_at'])
        );
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getWatchedAt() : DateTime
    {
        return $this->watchedAt;
    }
}
