<?php declare(strict_types=1);

namespace Movary\Application\User;

class Entity
{
    private function __construct(
        private readonly int $id,
        private readonly string $passwordHash
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self((int)$data['id'], $data['password']);
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getPasswordHash() : string
    {
        return $this->passwordHash;
    }
}
