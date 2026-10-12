<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\UserRepository;

class Validator
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    public function ensureEmailIsUnique(string $email, ?int $expectUserId = null) : void
    {
        $user = $this->repository->findUserByEmail($email);
        if ($user === null) {
            return;
        }

        if ($user->getId() !== $expectUserId) {
            throw new EmailNotUnique();
        }
    }

    public function ensureNameFormatIsValid(string $name) : void
    {
        preg_match('~^[a-zA-Z0-9]+$~', $name, $matches);
        if (empty($matches) === true) {
            throw new UsernameInvalidFormat();
        }
    }

    public function ensureNameIsUnique(string $name, ?int $expectUserId = null) : void
    {
        $user = $this->repository->findUserByName($name);
        if ($user === null) {
            return;
        }

        if ($user->getId() !== $expectUserId) {
            throw new UsernameNotUnique();
        }
    }
}
