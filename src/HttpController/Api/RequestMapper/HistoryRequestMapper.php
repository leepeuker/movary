<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\HistoryRequestDto;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;

class HistoryRequestMapper
{
    private const DEFAULT_LIMIT = 24;

    private const DEFAULT_PAGE = 1;

    private const DEFAULT_SORT_BY = 'watchedAt';

    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRequest(Request $request) : HistoryRequestDto
    {
        $getParameters = $request->getGetParameters();

        $searchTerm = $getParameters['search'] ?? null;
        $page = $getParameters['page'] ?? self::DEFAULT_PAGE;
        $limit = $getParameters['limit'] ?? self::DEFAULT_LIMIT;

        return HistoryRequestDto::create(
            $this->requestMapper->mapUsernameFromRoute($request)->getId(),
            $searchTerm,
            (int)$page,
            (int)$limit,
            self::DEFAULT_SORT_BY,
            SortOrder::createAsc(),
        );
    }
}
