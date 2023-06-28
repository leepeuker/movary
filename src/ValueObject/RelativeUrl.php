<?php declare(strict_types=1);

namespace Movary\ValueObject;

use JsonSerializable;
use Movary\ValueObject\Exception\InvalidRelativeUrl;

class RelativeUrl implements JsonSerializable
{
    //region instancing
    private function __construct(private readonly string $relativeUrl)
    {
        $this->ensureIsValidRelativeUrl($relativeUrl);
    }
    //endregion instancing

    //region methods
    public static function createFromString(string $url) : self
    {
        return new self($url);
    }

    public function __toString() : string
    {
        return $this->relativeUrl;
    }

    public function jsonSerialize() : string
    {
        return $this->relativeUrl;
    }

    private function ensureIsValidRelativeUrl(string $url) : void
    {
        if (str_starts_with($url, '/') === false || parse_url($url) === false) {
            throw new InvalidRelativeUrl('Invalid relative url: ' . $url);
        }
    }
    //endregion methods
}
