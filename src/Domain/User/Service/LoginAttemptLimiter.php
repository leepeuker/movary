<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\LoginAttemptLimitReached;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;

class LoginAttemptLimiter
{
    private const string ACCOUNT_SCOPE = 'account';

    private const string IP_SCOPE = 'ip';

    private const int ACCOUNT_ATTEMPT_LIMIT = 5;

    private const int IP_ATTEMPT_LIMIT = 20;

    private const int WINDOW_IN_SECONDS = 900;

    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }

    public function ensureAttemptIsAllowed(string $email, ?string $clientIp) : void
    {
        $now = DateTime::create();
        $windowStart = $now->subSeconds(self::WINDOW_IN_SECONDS);

        $retryAfterSeconds = $this->findRetryAfterSeconds(
            self::ACCOUNT_SCOPE,
            $this->hashSubject($this->normalizeEmail($email)),
            self::ACCOUNT_ATTEMPT_LIMIT,
            $now,
            $windowStart,
        );

        if ($clientIp !== null) {
            $retryAfterSeconds = max(
                $retryAfterSeconds,
                $this->findRetryAfterSeconds(
                    self::IP_SCOPE,
                    $this->hashSubject($clientIp),
                    self::IP_ATTEMPT_LIMIT,
                    $now,
                    $windowStart,
                ),
            );
        }

        if ($retryAfterSeconds > 0) {
            throw new LoginAttemptLimitReached($retryAfterSeconds);
        }
    }

    public function recordFailedAttempt(string $email, ?string $clientIp) : void
    {
        $now = DateTime::create();
        $this->repository->deleteLoginAttemptsBefore($now->subSeconds(self::WINDOW_IN_SECONDS));
        $this->repository->createLoginAttempt(
            self::ACCOUNT_SCOPE,
            $this->hashSubject($this->normalizeEmail($email)),
            $now,
        );

        if ($clientIp !== null) {
            $this->repository->createLoginAttempt(self::IP_SCOPE, $this->hashSubject($clientIp), $now);
        }
    }

    public function resetAccountAttempts(string $email) : void
    {
        $this->repository->deleteLoginAttemptsForSubject(
            self::ACCOUNT_SCOPE,
            $this->hashSubject($this->normalizeEmail($email)),
        );
    }

    private function findRetryAfterSeconds(
        string $scope,
        string $subjectHash,
        int $attemptLimit,
        DateTime $now,
        DateTime $windowStart,
    ) : int {
        $firstAttemptInLimitWindow = $this->repository->findLoginAttemptThresholdDate(
            $scope,
            $subjectHash,
            $attemptLimit,
            $windowStart,
        );

        if ($firstAttemptInLimitWindow === null) {
            return 0;
        }

        $retryAfterSeconds = (int)$firstAttemptInLimitWindow->format('U')
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
