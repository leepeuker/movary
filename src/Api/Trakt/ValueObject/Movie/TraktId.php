<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\Movie;

class TraktId
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function createFromInt(int $id) : self
    {
        return new self($id);
    }

    public static function createFromString(string $id) : self
    {
        return new self((int)$id);
    }

    public function asInt() : int
    {
        return $this->id;
    }

    public function isEqual(TraktId $traktId) : bool
    {
        return $this->id === $traktId->asInt();
    }
}
