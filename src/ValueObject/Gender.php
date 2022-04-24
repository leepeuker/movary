<?php declare(strict_types=1);

namespace Movary\ValueObject;

class Gender
{
    private const GENDER_FEMALE = 1;

    private const GENDER_MALE = 2;

    private const GENDER_NON_BINARY = 3;

    private const GENDER_UNKNOWN = 0;

    private const VALID_GENDERS = [self::GENDER_FEMALE, self::GENDER_MALE, self::GENDER_NON_BINARY, self::GENDER_UNKNOWN];

    private function __construct(private readonly int $gender)
    {
        self::ensureValidGender($gender);
    }

    public static function createFromInt(int $gender) : self
    {
        return new self($gender);
    }

    private static function ensureValidGender(int $gender) : void
    {
        if (in_array($gender, self::VALID_GENDERS, true) === false) {
            throw new \RuntimeException('Invalid gender :' . $gender);
        }
    }

    public function asInt() : int
    {
        return $this->gender;
    }
}
