<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use InvalidArgumentException;
use Movary\JobQueue\TmdbImageCacheJobParameters;
use Movary\ValueObject\Http\Request;

class TmdbImageCacheJobRequestMapper
{
    public function map(Request $request) : TmdbImageCacheJobParameters
    {
        $parameters = $request->getPostParameters();

        return TmdbImageCacheJobParameters::create(
            $this->mapIds($parameters['movieIds'] ?? null),
            $this->mapIds($parameters['personIds'] ?? null),
        );
    }

    private function mapIds(mixed $value) : array
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return [];
        }
        if (is_string($value) === false) {
            throw new InvalidArgumentException('IDs must be a comma-separated list of positive integers.');
        }

        $ids = [];
        foreach (explode(',', $value) as $id) {
            $id = trim($id);
            if (preg_match('/^[1-9][0-9]*$/D', $id) !== 1) {
                throw new InvalidArgumentException('IDs must contain only positive integers.');
            }

            $integer = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($integer === false) {
                throw new InvalidArgumentException('IDs must contain only positive integers.');
            }
            $ids[] = $integer;
        }

        return $ids;
    }
}
