<?php declare(strict_types=1);

namespace Movary\ValueObject;

class SortOrder
{
    private function __construct(private readonly string $value)
    {
    }

    public static function createAsc() : self
    {
        return new self('asc');
    }

    public static function createDesc() : self
    {
        return new self('desc');
    }

    public function __toString() : string
    {
        return $this->value;
    }
}
