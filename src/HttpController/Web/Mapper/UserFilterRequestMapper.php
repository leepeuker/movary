<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\ValueObject\Http\Request;

class UserFilterRequestMapper
{
    public function map(Request $request) : ?bool
    {
        $role = $request->getGetParameters()['role'] ?? null;

        return match ($role) {
            'admin' => true,
            'user' => false,
            default => null,
        };
    }
}
