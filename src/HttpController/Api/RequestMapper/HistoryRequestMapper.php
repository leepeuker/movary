<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\HistoryRequestDto;
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
}
