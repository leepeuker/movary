<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbCastMember
{
    private function __construct(
        private readonly TmdbCreditsPerson $person,
        private readonly ?string $character,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            TmdbCreditsPerson::createFromArray($data),
            $data['character'],
        );
    }

    public function getCharacter() : ?string
    {
        return $this->character;
    }

    public function getPerson() : TmdbCreditsPerson
    {
        return $this->person;
    }
}
