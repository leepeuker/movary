<?php declare(strict_types=1);

namespace Movary\Application\User\Service;

use Movary\Application\User\Repository;

class ChangePassword
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function changeAdminPassword(string $newPassword) : void
    {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->repository->updateAdminPassword($passwordHash);
    }
}
