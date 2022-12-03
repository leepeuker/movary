<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbCastMember
{
    private function __construct(
        private readonly int $castId,
        private readonly TmdbPerson $person,
        private readonly string $character,
        private readonly int $order
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['cast_id'],
            TmdbPerson::createFromArray($data),
            $data['character'],
            $data['order'],
        );
    }

    public function getCastId() : int
    {
        return $this->castId;
    }

    public function getCharacter() : string
    {
        return $this->character;
    }

    public function getOrder() : int
    {
        return $this->order;
    }

    public function getPerson() : TmdbPerson
    {
        return $this->person;
    }
}
