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
    ) {
    }

    public static function create(?Date $watchDate, int $plays, ?string $comment) : self
    {
        return new self($watchDate, $plays, $comment);
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
        ];
    }
}
