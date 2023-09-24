<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\SearchRequestDto;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Year;

class SearchRequestMapper
{
    private const DEFAULT_PAGE = 1;

    private const DEFAULT_YEAR = null;

    public function __construct()
    {
    }

    public function mapRequest(Request $request) : SearchRequestDto
    {
        $getParameters = $request->getGetParameters();

        $searchTerm = $getParameters['query'];
        $year = isset($getParameters['year']) === false ? self::DEFAULT_YEAR : Year::createFromString($getParameters['year']);
        $page = $getParameters['page'] ?? self::DEFAULT_PAGE;

        return SearchRequestDto::create(
            $searchTerm,
            (int)$page,
            $year,
        );
    }
}
