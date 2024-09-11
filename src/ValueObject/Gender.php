<?php declare(strict_types=1);

namespace Movary\ValueObject;

use RuntimeException;

class Gender
{
    private const array GENDER_ABBREVIATION = [
        self::GENDER_FEMALE => 'f',
        self::GENDER_MALE => 'm',
        self::GENDER_NON_BINARY => 'nb',
        self::GENDER_UNKNOWN => null,
    ];

    private const array GENDER_TEXT = [
        self::GENDER_FEMALE => 'Female',
        self::GENDER_MALE => 'Male',
        self::GENDER_NON_BINARY => 'Non Binary',
        self::GENDER_UNKNOWN => 'Unknown',
    ];

    private const int GENDER_FEMALE = 1;

    private const int GENDER_MALE = 2;

    private const int GENDER_NON_BINARY = 3;

    private const int GENDER_UNKNOWN = 0;

    private const array VALID_GENDERS = [self::GENDER_FEMALE, self::GENDER_MALE, self::GENDER_NON_BINARY, self::GENDER_UNKNOWN];

    private function __construct(private readonly int $gender)
    {
        self::ensureValidGender($gender);
    }

    public static function createFemale() : self
    {
        return new self(self::GENDER_FEMALE);
    }

    public static function createFromInt(int $gender) : self
    {
        return new self($gender);
    }

    public static function createMale() : self
    {
        return new self(self::GENDER_MALE);
    }

    private static function ensureValidGender(int $gender) : void
    {
        if (in_array($gender, self::VALID_GENDERS, true) === false) {
            throw new RuntimeException('Invalid gender :' . $gender);
        }
    }

    public function __toString() : string
    {
        return (string)$this->gender;
    }

    public function asInt() : int
    {
        return $this->gender;
    }

    public function getAbbreviation() : ?string
    {
        return self::GENDER_ABBREVIATION[$this->asInt()];
    }

    public function getText() : string
    {
        if (isset(self::GENDER_TEXT[$this->asInt()]) === false) {
            throw new RuntimeException('Could not get text for gender with id: ' . $this->asInt());
        }

        return self::GENDER_TEXT[$this->asInt()];
    }

    public function isEqual(Gender $gender) : bool
    {
        return $this->gender === $gender->gender;
    }
}
