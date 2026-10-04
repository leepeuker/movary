<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\LoginAttemptLimitReached;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;

class LoginAttemptLimiter
{
    private const int ACCOUNT_ATTEMPT_LIMIT = 5;

    private const int WINDOW_IN_SECONDS = 900;

    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }

    public function reserveAttempt(string $email) : int
    {
        $now = DateTime::create();
        $windowStart = $now->subSeconds(self::WINDOW_IN_SECONDS);
        $subjectHash = $this->hashSubject($this->normalizeEmail($email));

        $this->repository->deleteLoginAttemptsBefore($windowStart);
        // Reserve before verification so concurrent requests cannot all pass the limit check.
        $attemptId = $this->repository->createLoginAttempt($subjectHash, $now);
        $retryAfterSeconds = $this->findRetryAfterSeconds(
            $subjectHash,
            self::ACCOUNT_ATTEMPT_LIMIT,
            $now,
            $windowStart,
        );

        if ($retryAfterSeconds > 0) {
            $this->repository->deleteLoginAttempt($attemptId);

            throw new LoginAttemptLimitReached($retryAfterSeconds);
        }

        return $attemptId;
    }

    public function releaseAttempt(int $attemptId) : void
    {
        $this->repository->deleteLoginAttempt($attemptId);
    }

    /**
     * @return list<array{id: int, subjectHash: string, createdAt: DateTime}>
     */
    public function fetchAttempts() : array
    {
        return $this->repository->findLoginAttempts();
    }

    public function flushAttempts() : void
    {
        $this->repository->deleteAllLoginAttempts();
    }

    public function resetAccountAttempts(string $email) : void
    {
        $this->repository->deleteLoginAttemptsForSubject(
            $this->hashSubject($this->normalizeEmail($email)),
        );
    }

    private function findRetryAfterSeconds(
        string $subjectHash,
        int $attemptLimit,
        DateTime $now,
        DateTime $windowStart,
    ) : int {
        $firstAttemptAboveLimit = $this->repository->findLoginAttemptAboveLimitDate(
            $subjectHash,
            $attemptLimit,
            $windowStart,
        );

        if ($firstAttemptAboveLimit === null) {
            return 0;
        }

        $retryAfterSeconds = (int)$firstAttemptAboveLimit->format('U')
            + self::WINDOW_IN_SECONDS
            - (int)$now->format('U');

        return max(1, $retryAfterSeconds);
    }

    private function hashSubject(string $subject) : string
    {
        return hash('sha256', $subject);
    }

    private function normalizeEmail(string $email) : string
    {
        return mb_strtolower(trim($email));
    }
}
