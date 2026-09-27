<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;

class PasswordResetTokenService
{
    private const int EXPIRATION_TIME_IN_MINUTES = 15;

    private const int TOKEN_LENGTH_IN_BYTES = 32;

    public function __construct(private readonly UserRepository $repository)
    {
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

    private function hashToken(string $token) : string
    {
        return hash('sha256', $token);
    }
}
