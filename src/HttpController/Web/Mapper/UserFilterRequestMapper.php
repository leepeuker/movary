<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserFilter;
use Movary\ValueObject\Http\Request;

class UserFilterRequestMapper
{
    public function map(Request $request) : UserFilter
    {
        $role = $request->getGetParameters()['role'] ?? null;

        return UserFilter::create(match ($role) {
            'admin' => true,
            'user' => false,
            default => null,
        });
    }
}
