<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\WatchlistRequestDto;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;

class PlayedRequestMapper
{
    private const int MAX_SMALLINT = 32767;

    private const string DEFAULT_SORT_ORDER = 'asc';

    private const string DEFAULT_SORT_BY = 'title';

    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRequest(Request $request) : WatchlistRequestDto
    {
        $getParameters = $request->getGetParameters();

        $sortBy = $getParameters['sortBy'] ?? self::DEFAULT_SORT_BY;

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

    /** @return list<array{movieId: int, watchDates: list<array{watchedAt: null|Date, plays: int, comment: null|string}>}> */
    public function mapAdditions(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $addition) {
            $path = 'items[' . $index . ']';
            $result[] = [
                'movieId' => $this->requestMapper->mapExistingMovieId($addition, $path),
                'watchDates' => $this->mapAdditionWatchDates($addition, $path),
            ];
        }

        return $result;
    }

    /** @return list<array{movieId: int, watchDates: list<null|Date>}> */
    public function mapDeletions(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $deletion) {
            $path = 'items[' . $index . ']';
            $watchDates = [];
            foreach ($this->requestMapper->mapOptionalList($deletion, 'watchDates', $path . '.watchDates') as $dateIndex => $date) {
                $watchDates[] = $this->requestMapper->mapNullableDate(
                    $date,
                    $path . '.watchDates[' . $dateIndex . ']',
                );
            }

            $result[] = [
                'movieId' => $this->requestMapper->mapExistingMovieId($deletion, $path),
                'watchDates' => $watchDates,
            ];
        }

        return $result;
    }

    /** @return list<array{movieId: int, watchDates: list<array{watchedAt: null|Date, plays: int, comment: null|string}>}> */
    public function mapUpdates(Request $request) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapJsonObjectList($request) as $index => $update) {
            $path = 'items[' . $index . ']';
            $result[] = [
                'movieId' => $this->requestMapper->mapExistingMovieId($update, $path),
                'watchDates' => $this->mapUpdateWatchDates($update, $path),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $addition
     * @return list<array{watchedAt: null|Date, plays: int, comment: null|string}>
     */
    private function mapAdditionWatchDates(array $addition, string $path) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapOptionalObjectList($addition, 'watchDates', $path . '.watchDates') as $index => $watchDate) {
            $watchDatePath = $path . '.watchDates[' . $index . ']';
            $result[] = [
                'watchedAt' => $this->requestMapper->mapOptionalNullableDate(
                    $watchDate,
                    'watchedAt',
                    $watchDatePath . '.watchedAt',
                ),
                'plays' => $this->requestMapper->mapOptionalPositiveInteger(
                    $watchDate,
                    'plays',
                    $watchDatePath . '.plays',
                    1,
                    self::MAX_SMALLINT,
                ),
                'comment' => $this->requestMapper->mapOptionalNullableString(
                    $watchDate,
                    'comment',
                    $watchDatePath . '.comment',
                ),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $update
     * @return list<array{watchedAt: null|Date, plays: int, comment: null|string}>
     */
    private function mapUpdateWatchDates(array $update, string $path) : array
    {
        $result = [];
        foreach ($this->requestMapper->mapOptionalObjectList($update, 'watchDates', $path . '.watchDates') as $index => $watchDate) {
            $watchDatePath = $path . '.watchDates[' . $index . ']';
            $result[] = [
                'watchedAt' => $this->requestMapper->mapOptionalNullableDate(
                    $watchDate,
                    'watchedAt',
                    $watchDatePath . '.watchedAt',
                ),
                'plays' => $this->requestMapper->mapRequiredPositiveInteger(
                    $watchDate,
                    'plays',
                    $watchDatePath . '.plays',
                    self::MAX_SMALLINT,
                ),
                'comment' => $this->requestMapper->mapOptionalNullableString(
                    $watchDate,
                    'comment',
                    $watchDatePath . '.comment',
                ),
            ];
        }

        return $result;
    }
}
