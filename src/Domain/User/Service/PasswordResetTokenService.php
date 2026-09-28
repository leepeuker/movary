<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;

class PasswordResetTokenService
{
    public const int EXPIRATION_TIME_IN_MINUTES = 15;

    private const int CREATION_COOLDOWN_IN_SECONDS = 60;

    private const int TOKEN_LENGTH_IN_BYTES = 32;

    public function __construct(
        private readonly UserRepository $repository,
        private readonly Validator $validator,
    ) {
    }

    public function createTokenIfAllowed(int $userId) : ?string
    {
        $lastCreationDate = $this->repository->findPasswordResetTokenCreationDate($userId);
        $cooldownThreshold = DateTime::create()->subSeconds(self::CREATION_COOLDOWN_IN_SECONDS);

        if ($lastCreationDate?->isAfter($cooldownThreshold) === true) {
            return null;
        }

        return $this->createToken($userId);
    }

    public function createToken(int $userId) : string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH_IN_BYTES));
        $expirationDate = DateTime::createFromString('+' . self::EXPIRATION_TIME_IN_MINUTES . ' minutes');

        $this->repository->replacePasswordResetToken(
            $userId,
            $this->hashToken($token),
            $expirationDate,
        );

        return $token;
    }

    public function resetPassword(string $token, string $newPassword) : bool
    {
        $this->validator->ensurePasswordIsValid($newPassword);

        return $this->repository->resetPasswordWithToken(
            $this->hashToken($token),
            password_hash($newPassword, PASSWORD_DEFAULT),
            DateTime::create(),
        );
    }

    public function deleteAllTokens() : void
    {
        $this->repository->deleteAllPasswordResetTokens();
    }

    public function deleteToken(string $token) : void
    {
        $this->repository->deletePasswordResetToken($this->hashToken($token));
    }

    public function deleteTokenForUser(int $userId) : void
    {
        $this->repository->deletePasswordResetTokenForUser($userId);
    }

    public function findUserIdByToken(string $token) : ?int
    {
        $tokenHash = $this->hashToken($token);
        $tokenData = $this->repository->findPasswordResetTokenData($tokenHash);

        if ($tokenData === null) {
            return null;
        }

        if ($tokenData['expirationDate']->isAfter(DateTime::create()) === false) {
            $this->repository->deletePasswordResetToken($tokenHash);

            return null;
        }

        return $tokenData['userId'];
    }

    /**
     * @return array<array{userId: int, name: string, email: string, expirationDate: DateTime, createdAt: DateTime}>
     */
    public function fetchPendingTokens() : array
    {
        return $this->repository->fetchPendingPasswordResetTokens(DateTime::create());
    }

    private function hashToken(string $token) : string
    {
        return hash('sha256', $token);
    }
}
