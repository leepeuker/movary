<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

use JsonSerializable;

class MovieHistoryLocationEntity implements JsonSerializable
{
    private function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $name,
        private readonly bool $isCinema,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            (int)$data['user_id'],
            (string)$data['name'],
            (bool)$data['is_cinema'],
        );
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }

    public function jsonSerialize() : array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isCinema' => $this->isCinema,
        ];
    }
}
