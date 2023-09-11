<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\ValueObject\Http\Request;

class RequestMapper
{
    public function __construct(
        private readonly UserApi $userApi,
    ) {
    }

    public function mapUsernameFromRoute(Request $request) : UserEntity
    {
        $username = $request->getRouteParameters()['username'] ?? null;

        if ($username === null) {
            throw new \RuntimeException('Username parameter missing in route');
        }

        return $this->userApi->fetchUserByName($username);
    }
}
