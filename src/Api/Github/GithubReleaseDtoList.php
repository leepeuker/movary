<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Movary\ValueObject\AbstractList;

/**
 * @method GithubReleaseDto[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class GithubReleaseDtoList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(GithubReleaseDto $release) : void
    {
        $this->data[] = $release;
    }
}
