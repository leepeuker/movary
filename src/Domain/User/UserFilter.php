<?php declare(strict_types=1);

namespace Movary\Domain\User;

class UserFilter
{
    private function __construct(private readonly ?bool $isAdmin)
    {
    }

    public static function create(?bool $isAdmin = null) : self
    {
        return new self($isAdmin);
    }

    public function getIsAdmin() : ?bool
    {
        return $this->isAdmin;
    }

    public function hasFilters() : bool
    {
        return $this->isAdmin !== null;
    }
}
