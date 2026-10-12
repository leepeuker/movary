<?php declare(strict_types=1);

namespace Movary\ValueObject;

use InvalidArgumentException;

class MovieTitleDisplay
{
    private const string LOCALIZED = 'localized';

    private const string ORIGINAL = 'original';

    private const string BOTH = 'both';

    private function __construct(private readonly string $value)
    {
    }

    public static function createLocalized() : self
    {
        return new self(self::LOCALIZED);
    }

    public static function createFromString(string $value) : self
    {
        if (in_array($value, [self::LOCALIZED, self::ORIGINAL, self::BOTH], true) === false) {
            throw new InvalidArgumentException('Invalid movie title display option: ' . $value);
        }

        return new self($value);
    }

    public function __toString() : string
    {
        return $this->value;
    }

    public function displaysBoth() : bool
    {
        return $this->value === self::BOTH;
    }

    public function displaysOriginal() : bool
    {
        return $this->value === self::ORIGINAL;
    }
}
