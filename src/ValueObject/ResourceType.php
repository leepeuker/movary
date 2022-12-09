<?php declare(strict_types=1);

namespace Movary\ValueObject;

class ResourceType
{
    private function __construct(private readonly string $type)
    {
    }

    public static function createMovie() : self
    {
        return new self('movie');
    }

    public static function createPerson() : self
    {
        return new self('person');
    }

    public function isEqual(self $resourceType) : bool
    {
        return $this->type === $resourceType->type;
    }

    public function isMovie() : bool
    {
        return $this->isEqual(self::createMovie());
    }

    public function isPerson() : bool
    {
        return $this->isEqual(self::createPerson());
    }

    public function __toString() : string
    {
        return $this->type;
    }
}
