<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\WatchlistRequestDto;
use Movary\ValueObject\Http\Request;

class WatchlistRequestMapper
{
    private const string DEFAULT_SORT_BY = 'addedAt';

    private const string DEFAULT_SORT_ORDER = 'desc';

    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRequest(Request $request) : WatchlistRequestDto
    {
        $sortBy = $request->getGetParameters()['sortBy'] ?? self::DEFAULT_SORT_BY;

        return WatchlistRequestDto::create(
            $this->requestMapper->mapUsernameFromRoute($request)->getId(),
            $this->requestMapper->mapSearchTerm($request),
            $this->requestMapper->mapPage($request),
            $this->requestMapper->mapLimit($request),
            $sortBy,
            $this->requestMapper->mapSortOrder($request, self::DEFAULT_SORT_ORDER),
            $this->requestMapper->mapReleaseYear($request),
        );
    }

    /** @return list<int> */
    public function mapAdditions(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $addition) {
            $result[] = $this->requestMapper->mapExistingMovieId($addition, 'items[' . $index . ']');
        }

        return $result;
    }

    /** @return list<int> */
    public function mapDeletions(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $deletion) {
            $result[] = $this->requestMapper->mapRequiredPositiveInteger(
                $deletion,
                'movaryId',
                'items[' . $index . '].movaryId',
            );
        }

        return $result;
    }
}
