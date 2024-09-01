<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

use Movary\ValueObject\DateTime;

class MovieHistoryLocationEntity implements \JsonSerializable
{
    private function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $name,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            (int)$data['user_id'],
            (string)$data['name'],
        );
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
        ];
    }
}