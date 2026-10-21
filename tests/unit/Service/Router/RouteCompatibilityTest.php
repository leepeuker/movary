<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Router;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class RouteCompatibilityTest extends TestCase
{
    public function testUnversionedApiAliasesMatchVersionOneRoutes() : void
    {
        $operations = $this->collectOperations();

        foreach (array_keys($operations) as $operation) {
            if (str_contains($operation, ' /api/v1/') === false) {
                continue;
            }

            $legacyOperation = str_replace(' /api/v1/', ' /api/', $operation);
            self::assertArrayHasKey(
                $legacyOperation,
                $operations,
                'Missing unversioned API compatibility route for ' . $operation,
            );
            self::assertSame($operations[$operation], $operations[$legacyOperation]);
        }
    }

    public function testLegacyIntegrationAliasesMatchCanonicalRoutes() : void
    {
        $operations = $this->collectOperations();

        foreach (array_keys($operations) as $operation) {
            if (str_contains($operation, ' /webhooks/') === true) {
                $legacyOperation = str_replace(' /webhooks/', ' /api/webhook/', $operation);
            } elseif (str_contains($operation, ' /feeds/') === true) {
                $legacyOperation = str_replace(' /feeds/', ' /api/feed/', $operation);
            } else {
                continue;
            }

            self::assertArrayHasKey(
                $legacyOperation,
                $operations,
                'Missing legacy integration compatibility route for ' . $operation,
            );
            self::assertSame($operations[$operation], $operations[$legacyOperation]);
        }
    }

    /** @return array<string, array{handler: mixed, middleware: array<string>}> */
    private function collectOperations() : array
    {
        $operations = [];
        foreach (RegisteredRoutes::collect() as $route) {
            $operations[$route['method'] . ' ' . $route['path']] = [
                'handler' => $route['handler'],
                'middleware' => $route['middleware'],
            ];
        }

        return $operations;
    }
}
