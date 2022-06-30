<?php declare(strict_types=1);

namespace Movary\Application\User\Service;

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

    public function generateToken(?DateTime $expirationDate = null) : string
    {
        if ($expirationDate === null) {
            $expirationDate = DateTime::createFromString(date('Y-m-d H:i:s', strtotime('+1 day', time())));
        }

        $token = bin2hex(random_bytes(16));

        $this->repository->createAuthToken($token, $expirationDate);

        return $token;
    }

    public function isValidToken(string $token) : bool
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
