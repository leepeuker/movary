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
        $imageType = $parameters['imageType'] ?? null;
        if (is_string($imageType) === false) {
            throw new InvalidArgumentException('Image type is invalid.');
        }

        return TmdbImageCacheJobParameters::create(
            $imageType,
            $this->mapBoolean($parameters['forceRefresh'] ?? null),
        );
    }

    private function mapBoolean(mixed $value) : bool
    {
        if ($value === null) {
            return false;
        }
        if ($value === '1' || $value === 1 || $value === true) {
            return true;
        }

        throw new InvalidArgumentException('Force refresh must be a boolean.');
    }
}
