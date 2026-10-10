<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use InvalidArgumentException;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;

class PersonalApiTokenService
{
    public const array EXPIRATION_DAYS = [30, 90, 365];

    private const int MAX_NAME_LENGTH = 100;

    private const int LAST_USED_UPDATE_INTERVAL_SECONDS = 900;

    private const string TOKEN_PREFIX = 'mvy_pat_v1_';

    private const int TOKEN_PREFIX_RANDOM_LENGTH = 8;

    private const int TOKEN_SECRET_LENGTH_IN_BYTES = 32;

    public function __construct(private readonly UserRepository $repository)
    {
    }

    /** @return array{token: string, expiresAt: null|DateTime} */
    public function createToken(int $userId, string $name, ?int $expirationDays) : array
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException('Personal API token names must contain between 1 and 100 characters.');
        }
        if ($expirationDays !== null && in_array($expirationDays, self::EXPIRATION_DAYS, true) === false) {
            throw new InvalidArgumentException('Unsupported personal API token expiration.');
        }

        $token = self::TOKEN_PREFIX . $this->generateSecret();
        $createdAt = DateTime::create();
        $expiresAt = $expirationDays === null
            ? null
            : DateTime::createFromString('+' . $expirationDays . ' days');

        $this->repository->createPersonalApiToken(
            $userId,
            $name,
            $this->hashToken($token),
            substr($token, 0, strlen(self::TOKEN_PREFIX) + self::TOKEN_PREFIX_RANDOM_LENGTH),
            $createdAt,
            $expiresAt,
        );

        return ['token' => $token, 'expiresAt' => $expiresAt];
    }

    public function findUserIdByToken(string $token) : ?int
    {
        if ($this->isSupportedTokenFormat($token) === false) {
            return null;
        }

        $tokenData = $this->repository->findPersonalApiTokenData($this->hashToken($token));
        if ($tokenData === null) {
            return null;
        }
        $now = DateTime::create();
        if ($tokenData['expiresAt'] !== null && $tokenData['expiresAt']->isAfter($now) === false) {
            return null;
        }

        $lastUsedUpdateThreshold = $now->subSeconds(self::LAST_USED_UPDATE_INTERVAL_SECONDS);
        if ($tokenData['lastUsedAt'] === null || $tokenData['lastUsedAt']->isAfter($lastUsedUpdateThreshold) === false) {
            $this->repository->updatePersonalApiTokenLastUsedAt(
                $tokenData['id'],
                $now,
                $lastUsedUpdateThreshold,
            );
        }

        return $tokenData['userId'];
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     tokenPrefix: string,
     *     createdAt: DateTime,
     *     lastUsedAt: null|DateTime,
     *     expiresAt: null|DateTime
     * }>
     */
    public function fetchTokensPaginated(int $userId, int $limit, int $offset) : array
    {
        return $this->repository->fetchPersonalApiTokensPaginated($userId, $limit, $offset);
    }

    public function countTokens(int $userId) : int
    {
        return $this->repository->countPersonalApiTokens($userId);
    }

    public function revokeToken(int $userId, int $tokenId) : void
    {
        $this->repository->deletePersonalApiToken($userId, $tokenId);
    }

    public function revokeAllTokens(int $userId) : void
    {
        $this->repository->deleteAllPersonalApiTokens($userId);
    }

    private function generateSecret() : string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::TOKEN_SECRET_LENGTH_IN_BYTES)), '+/', '-_'), '=');
    }

    private function hashToken(string $token) : string
    {
        return hash('sha256', $token);
    }

    private function isSupportedTokenFormat(string $token) : bool
    {
        return preg_match('/\Amvy_pat_v1_[A-Za-z0-9_-]{43}\z/D', $token) === 1
            || preg_match('/\A[0-9a-f]{32}\z/Di', $token) === 1
            || preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/Di', $token) === 1;
    }
}
