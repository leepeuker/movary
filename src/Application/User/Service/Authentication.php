<?php declare(strict_types=1);

namespace Movary\Application\User\Service;

use Movary\Application\User\Exception\InvalidPassword;
use Movary\Application\User\Repository;
use Movary\ValueObject\DateTime;

class Authentication
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function deleteToken(string $token) : void
    {
        $this->repository->deleteAuthToken($token);
    }

    public function isUserAuthenticated() : bool
    {
        $token = filter_input(INPUT_COOKIE, 'id');

        if (empty($token) === false && $this->isValidToken($token) === true) {
            return true;
        }

        if (empty($token) === false) {
            unset($_COOKIE['id']);
            setcookie('id', '', -1);
        }

        return false;
    }

    public function login(string $password, bool $rememberMe) : void
    {
        $user = $this->repository->fetchAdminUser();

        if (password_verify($password, $user->getPasswordHash()) === false) {
            throw InvalidPassword::create();
        }

        $expirationDate = $this->createExpirationDate();
        if ($rememberMe === true) {
            $expirationDate = $this->createExpirationDate(30);
        }

        $token = $this->generateToken(DateTime::createFromString((string)$expirationDate));

        setcookie('id', $token, (int)$expirationDate->format('U'));
    }

    private function createExpirationDate(int $days = 1) : DateTime
    {
        $timestamp = strtotime('+' . $days . ' day');

        if ($timestamp === false) {
            throw new \RuntimeException('Could not generate timestamp for auth token expiration date.');
        }

        return DateTime::createFromString(date('Y-m-d H:i:s', $timestamp));
    }

    private function generateToken(?DateTime $expirationDate = null) : string
    {
        if ($expirationDate === null) {
            $expirationDate = $this->createExpirationDate();
        }

        $token = bin2hex(random_bytes(16));

        $this->repository->createAuthToken($token, $expirationDate);

        return $token;
    }

    private function isValidToken(string $token) : bool
    {
        $tokenExpirationDate = $this->repository->findAuthTokenExpirationDate($token);

        if ($tokenExpirationDate === null || $tokenExpirationDate->isAfter(DateTime::create()) === false) {
            if ($tokenExpirationDate !== null) {
                $this->repository->deleteAuthToken($token);
            }

            return false;
        }

        return true;
    }
}
