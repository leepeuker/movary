<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\HistoryRequestDto;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;

class HistoryRequestMapper
{
    private const string DEFAULT_SORT_BY = 'watchedAt';

    private const string DEFAULT_SORT_ORDER = 'desc';

    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRequest(Request $request) : HistoryRequestDto
    {
        return HistoryRequestDto::create(
            $this->requestMapper->mapUsernameFromRoute($request)->getId(),
            $this->requestMapper->mapSearchTerm($request),
            $this->requestMapper->mapPage($request),
            $this->requestMapper->mapLimit($request),
            self::DEFAULT_SORT_BY,
            $this->requestMapper->mapSortOrder($request, self::DEFAULT_SORT_ORDER),
        );
    }

    /** @return list<array{movieId: int, watchedAt: Date, plays: int, position: int, comment: null|string}> */
    public function mapAdditions(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $addition) {
            $path = 'items[' . $index . ']';
            $result[] = [
                'movieId' => $this->requestMapper->mapExistingMovieId($addition, $path),
                'watchedAt' => $this->requestMapper->mapRequiredDate($addition, 'watchedAt', $path . '.watchedAt'),
                'plays' => $this->requestMapper->mapOptionalPositiveInteger($addition, 'plays', $path . '.plays', 1),
                'position' => $this->requestMapper->mapOptionalPositiveInteger($addition, 'position', $path . '.position', 1),
                'comment' => $this->requestMapper->mapOptionalNullableString($addition, 'comment', $path . '.comment'),
            ];
        }

        return $result;
    }

    /** @return list<array{movieId: int, watchedAt: Date}> */
    public function mapDeletions(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $deletion) {
            $path = 'items[' . $index . ']';
            $result[] = [
                'movieId' => $this->requestMapper->mapExistingMovieId($deletion, $path),
                'watchedAt' => $this->requestMapper->mapRequiredDate($deletion, 'watchedAt', $path . '.watchedAt'),
            ];
        }

        return $result;
    }

    /** @return list<array{movieId: int, watchedAt: Date, plays: int, position: int, comment: null|string}> */
    public function mapUpdates(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $update) {
            $path = 'items[' . $index . ']';
            $result[] = [
                'movieId' => $this->requestMapper->mapExistingMovieId($update, $path),
                'watchedAt' => $this->requestMapper->mapRequiredDate($update, 'watchedAt', $path . '.watchedAt'),
                'plays' => $this->requestMapper->mapRequiredPositiveInteger($update, 'plays', $path . '.plays'),
                'position' => $this->requestMapper->mapRequiredPositiveInteger($update, 'position', $path . '.position'),
                'comment' => $this->requestMapper->mapRequiredNullableString($update, 'comment', $path . '.comment'),
            ];
        }

        return $result;
    }
}
