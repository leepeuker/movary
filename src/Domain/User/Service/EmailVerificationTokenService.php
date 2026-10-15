<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;

class EmailVerificationTokenService
{
    public const int EXPIRATION_TIME_IN_HOURS = 24;

    private const int CREATION_COOLDOWN_IN_SECONDS = 60;

    private const int TOKEN_LENGTH_IN_BYTES = 32;

    public function __construct(private readonly UserRepository $repository)
    {
    }

    public function createTokenIfAllowed(int $userId, string $email) : ?string
    {
        $lastCreationDate = $this->repository->findEmailVerificationTokenCreationDate($userId);
        $cooldownThreshold = DateTime::create()->subSeconds(self::CREATION_COOLDOWN_IN_SECONDS);

        if ($lastCreationDate?->isAfter($cooldownThreshold) === true) {
            return null;
        }

        return $this->createToken($userId, $email);
    }

    public function createToken(int $userId, string $email) : string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH_IN_BYTES));
        $expirationDate = DateTime::createFromString('+' . self::EXPIRATION_TIME_IN_HOURS . ' hours');

        $this->repository->replaceEmailVerificationToken(
            $userId,
            $email,
            $this->hashToken($token),
            $expirationDate,
        );

        return $token;
    }

    public function verifyEmail(string $token) : bool
    {
        return $this->repository->verifyEmailWithToken($this->hashToken($token), DateTime::create());
    }

    public function findUserIdByToken(string $token) : ?int
    {
        $tokenHash = $this->hashToken($token);
        $tokenData = $this->repository->findEmailVerificationTokenData($tokenHash);

        if ($tokenData === null) {
            return null;
        }

        $emailMatchesPendingAddress = $tokenData['pendingEmail'] !== null
            && $tokenData['email'] === $tokenData['pendingEmail'];
        $emailMatchesUnverifiedCurrentAddress = $tokenData['pendingEmail'] === null
            && $tokenData['email'] === $tokenData['currentEmail'];
        if (($emailMatchesPendingAddress === false && $emailMatchesUnverifiedCurrentAddress === false)
            || $tokenData['expirationDate']->isAfter(DateTime::create()) === false
        ) {
            $this->repository->deleteEmailVerificationToken($tokenHash);

            return null;
        }

        return $tokenData['userId'];
    }

    public function deleteToken(string $token) : void
    {
        $this->repository->deleteEmailVerificationToken($this->hashToken($token));
    }

    public function deleteTokenForUser(int $userId) : void
    {
        $this->repository->deleteEmailVerificationTokenForUser($userId);
    }

    private function hashToken(string $token) : string
    {
        return hash('sha256', $token);
    }
}
