<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @method TmdbWatchProviderDto[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class TmdbWatchProviderList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genreDate) {
            $list->add(TmdbWatchProviderDto::createFromArray($genreDate));
        }

        return $list;
    }

    public function add(TmdbWatchProviderDto $watchProvider) : void
    {
        $this->data[] = $watchProvider;
    }
}
