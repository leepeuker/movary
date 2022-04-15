<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class CastMember
{
    private int $castId;

    private string $character;

    private int $order;

    private Person $person;

    private function __construct(int $castId, Person $person, string $character, int $order)
    {
        $this->castId = $castId;
        $this->person = $person;
        $this->character = $character;
        $this->order = $order;
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
