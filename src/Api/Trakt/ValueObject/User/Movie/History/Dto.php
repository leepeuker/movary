<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\History;

use Movary\Api\Trakt\ValueObject\Movie;
use Movary\ValueObject\DateTime;

class Dto
{
    private string $action;

    private int $id;

    private Movie\Dto $movie;

    private DateTime $watchedAt;

    private function __construct(int $id, Movie\Dto $movie, DateTime $watchedAt, string $action)
    {
        $this->id = $id;
        $this->movie = $movie;
        $this->watchedAt = $watchedAt;
        $this->action = $action;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            Movie\Dto::createFromArray($data['movie']),
            DateTime::createFromString($data['watched_at']),
            $data['action'],
        );
    }

    public function getMovie() : Movie\Dto
    {
        return $this->movie;
    }

    public function getWatchedAt() : DateTime
    {
        return $this->watchedAt;
    }
}
