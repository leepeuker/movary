<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<string>
 */
class TmdbCountryList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $countryData) {
            $list->add($countryData['iso_3166_1']);
        }

        return $list;
    }

    public function add(string $country) : void
    {
        $this->data[] = $country;
    }
}
