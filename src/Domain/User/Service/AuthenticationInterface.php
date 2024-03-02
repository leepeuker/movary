<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserEntity;
use Movary\ValueObject\Http\Request;

interface AuthenticationInterface
{
    public function getCurrentUser(Request $request) : UserEntity;

    public function getToken(Request $request) : ?string;

    public function isUserAuthenticated(Request $request) : bool;
}
