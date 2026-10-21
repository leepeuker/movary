<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Router;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class ApiOpenApiParityTest extends TestCase
{
    private const array HTTP_METHODS = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'];

    private const array EXCLUDED_ROUTES = [
        'GET /api/openapi' => 'This route serves the OpenAPI contract itself.',
    ];

    public function testEveryApiRouteIsDocumented() : void
    {
        $undocumentedRoutes = array_values(array_diff(
            $this->collectRegisteredApiOperations(),
            array_keys($this->collectDocumentedOperations()),
        ));

        self::assertSame([], $undocumentedRoutes, 'Undocumented API routes: ' . implode(', ', $undocumentedRoutes));
    }

    public function testEveryDocumentedOperationHasARegisteredRoute() : void
    {
        $unimplementedOperations = array_values(array_diff(
            array_keys($this->collectDocumentedOperations()),
            $this->collectRegisteredApiOperations(),
        ));

        self::assertSame(
            [],
            $unimplementedOperations,
            'Documented operations without a registered route: ' . implode(', ', $unimplementedOperations),
        );
    }

    public function testEveryDocumentedOperationHasAUniqueOperationId() : void
    {
        $operationIds = $this->collectDocumentedOperations();
        $operationsWithoutId = array_keys($operationIds, null, true);

        self::assertSame(
            [],
            $operationsWithoutId,
            'Documented operations without an operationId: ' . implode(', ', $operationsWithoutId),
        );

        $ids = array_values(array_filter($operationIds));
        $duplicateIds = array_keys(array_filter(
            array_count_values($ids),
            static fn(int $count) : bool => $count > 1,
        ));

        self::assertSame([], $duplicateIds, 'Duplicate operationIds: ' . implode(', ', $duplicateIds));
    }

    public function testExcludedRoutesAreRegistered() : void
    {
        $registeredRoutes = array_map(
            static fn(array $route) : string => $route['method'] . ' ' . $route['path'],
            RegisteredRoutes::collect(),
        );

        foreach (self::EXCLUDED_ROUTES as $route => $reason) {
            self::assertContains($route, $registeredRoutes, "Stale OpenAPI exclusion: $route ($reason)");
        }
    }

    /** @return list<string> */
    private function collectRegisteredApiOperations() : array
    {
        $operations = [];
        foreach (RegisteredRoutes::collect() as $route) {
            if (str_starts_with($route['path'], '/api/') === false) {
                continue;
            }

            $operation = $route['method'] . ' ' . $route['path'];
            if (isset(self::EXCLUDED_ROUTES[$operation]) === true) {
                continue;
            }

            $operations[] = $route['method'] . ' ' . $this->normalizePath(substr($route['path'], 4));
        }

        sort($operations);

        return $operations;
    }

    /** @return array<string, null|string> */
    private function collectDocumentedOperations() : array
    {
        $openApiJson = file_get_contents(dirname(__DIR__, 4) . '/docs/openapi.json');
        if ($openApiJson === false) {
            throw new \RuntimeException('Could not read docs/openapi.json.');
        }

        $openApi = json_decode($openApiJson, true, flags: JSON_THROW_ON_ERROR);
        if (is_array($openApi) === false || isset($openApi['paths']) === false || is_array($openApi['paths']) === false) {
            throw new \UnexpectedValueException('OpenAPI document does not contain a paths object.');
        }

        $operations = [];
        foreach ($openApi['paths'] as $path => $pathItem) {
            if (is_string($path) === false || is_array($pathItem) === false) {
                throw new \UnexpectedValueException('OpenAPI path item has an invalid shape.');
            }

            foreach ($pathItem as $method => $operation) {
                if (is_string($method) === false || in_array($method, self::HTTP_METHODS, true) === false) {
                    continue;
                }
                if (is_array($operation) === false) {
                    throw new \UnexpectedValueException("OpenAPI operation $method $path has an invalid shape.");
                }

                $operationId = $operation['operationId'] ?? null;
                $operations[strtoupper($method) . ' ' . $this->normalizePath($path)] = is_string($operationId)
                    ? $operationId
                    : null;
            }
        }

        ksort($operations);

        return $operations;
    }

    private function normalizePath(string $path) : string
    {
        return preg_replace('/\{[^}]+\}/', '{}', $path) ?? $path;
    }
}
