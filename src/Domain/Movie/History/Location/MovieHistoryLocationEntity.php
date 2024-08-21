<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

use Movary\ValueObject\DateTime;

class MovieHistoryLocationEntity
{
    private function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $name,
        private readonly DateTime $createdAt,
        private readonly DateTime $updatedAt,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            (int)$data['user_id'],
            (string)$data['name'],
            DateTime::createFromString((string)$data['created_at']),
            DateTime::createFromString((string)$data['updated_at']),
        );
    }

}
