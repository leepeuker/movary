<?php declare(strict_types=1);

namespace Movary\Application\User\Exception;

class PasswordTooShort extends \Exception
{
    public function __construct(private readonly int $minLength)
    {
        parent::__construct();
    }

    public function getMinLength() : int
    {
        return $this->minLength;
    }
}
