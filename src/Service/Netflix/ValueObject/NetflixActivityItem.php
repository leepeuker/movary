<?php declare(strict_types=1);

namespace Movary\Service\Netflix\ValueObject;

use Movary\ValueObject\Date;

class NetflixActivityItem
{
    private function __construct(
        private readonly string $title,
        private readonly Date $date,
    ) {
    }

    public static function create(string $title, Date $date) : self
    {
        return new self($title, $date);
    }

    public function getDate() : Date
    {
        return $this->date;
    }

    public function getTitle() : string
    {
        return $this->title;
    }
}
