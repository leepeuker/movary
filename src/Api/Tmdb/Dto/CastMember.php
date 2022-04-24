<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class CastMember
{
    private function __construct(
        private readonly int $castId,
        private readonly Person $person,
        private readonly string $character,
        private readonly int $order
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['cast_id'],
            Person::createFromArray($data),
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

    public function getPerson() : Person
    {
        return $this->person;
    }
}
