<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

class LoginAttemptLimitReached extends InvalidCredentials
{
    public function __construct(
        private readonly int $retryAfterSeconds,
    ) {
        parent::__construct('Too many login attempts.');
    }

    public function getRetryAfterSeconds() : int
    {
        return $this->retryAfterSeconds;
    }
}
