<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Movary\ValueObject\Date;

class MovieHistoryEntity
{
    private function __construct(
        private readonly int $movieId,
        private readonly ?Date $watchedAt,
        private readonly int $plays,
        private readonly ?string $comment,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['movie_id'],
            $data['watched_at'] == null ? null : Date::createFromString($data['watched_at']),
            $data['plays'],
            $data['comment'],
        );
    }

    public function getComment() : ?string
    {
        return $this->comment;
    }

    public function getMovieId() : int
    {
        return $this->movieId;
    }

    public function getPlays() : int
    {
        return $this->plays;
    }

    public function getWatchedAt() : ?Date
    {
        return $this->watchedAt;
    }
}
