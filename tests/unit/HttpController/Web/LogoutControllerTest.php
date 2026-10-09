<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Web\LogoutController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\HttpController\Web\LogoutController::class)]
class LogoutControllerTest extends TestCase
{
    public function testLogoutClearsBrowserAuthentication() : void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('logout');

        $response = (new LogoutController($authentication))->logout();

        self::assertSame(204, $response->getStatusCode()->getCode());
    }
}
