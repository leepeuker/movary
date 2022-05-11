<?php declare(strict_types=1);

namespace Movary\Application\User;

use Doctrine\DBAL\Connection;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function fetchAdminUser() : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `user` WHERE `id` = 1');

        if (empty($data) === true) {
            throw new \RuntimeException('Admin user is missing.');
        }

        return Entity::createFromArray($data);
    }
}
