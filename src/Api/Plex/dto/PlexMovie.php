<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

class PlexMovie
{
    public function __construct(
        private readonly int $movieId
    ){
    }

    public static function createPlexMovie(int $movieId) : self
    {
        return new self($movieId);
    }

    public function getPlexMovieId() : int
    {
        return $this->movieId;
    }
}