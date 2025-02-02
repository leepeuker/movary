<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;
use Movary\ValueObject\Date;

class WatchDateDto implements JsonSerializable
{
    public function __construct(
        private readonly ?Date $watchDate,
        private readonly int $plays,
        private readonly ?string $comment,
        private readonly ?int $locationId,
    ) {
    }

    public static function create(?Date $watchDate, int $plays, ?string $comment, ?int $locationId) : self
    {
        return new self($watchDate, $plays, $comment, $locationId);
    }

    public function getWatchDate() : ?Date
    {
        return $this->watchDate;
    }

    public function jsonSerialize() : array
    {
        return [
            'date' => $this->watchDate,
            'plays' => $this->plays,
            'comment' => $this->comment,
            'locationId' => $this->locationId,
        ];
    }
}
