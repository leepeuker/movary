<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\HttpController\Api\ServerEmailController;
use Movary\HttpController\Api\RequestMapper\ServerEmailRequestMapper;
use Movary\Service\Email\EmailSettingsConflict;
use Movary\Service\Email\ServerEmailSettingsService;
use Movary\Service\ServerSettings;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerEmailController::class)]
class ServerEmailControllerTest extends TestCase
{
    public function testGetRedactsCredentialsAndPreventsCaching() : void
    {
        $settings = $this->createStub(ServerSettings::class);
        $settings->method('getSmtpUser')->willReturn('private-user');
        $settings->method('getSmtpPassword')->willReturn('private-password');
        $service = $this->createStub(ServerEmailSettingsService::class);
        $service->method('getEnvironmentControlledFields')->willReturn(['smtpPassword']);
        $controller = new ServerEmailController($settings, $service, $this->createStub(ServerEmailRequestMapper::class));

        $response = $controller->get();
        $body = (string)$response->getBody();
        $data = json_decode($body, true);
        self::assertTrue($data['smtpUserConfigured']);
        self::assertTrue($data['smtpPasswordConfigured']);
        self::assertSame(['smtpPassword'], $data['environmentControlledFields']);
        self::assertArrayNotHasKey('smtpUser', $data);
        self::assertArrayNotHasKey('smtpPassword', $data);
        self::assertStringNotContainsString('private-user', $body);
        self::assertStringNotContainsString('private-password', $body);
        $headers = [];
        foreach ($response->getHeaders() as $header) {
            $headers[$header->getName()] = (string)$header;
        }
        self::assertSame('Cache-Control: no-store', $headers['Cache-Control']);
    }

    public function testPatchRejectsEnvironmentFieldsBeforeSavingAnything() : void
    {
        $service = $this->createMock(ServerEmailSettingsService::class);
        $service->method('getEnvironmentControlledFields')->willReturn(['smtpPassword']);
        $service->expects(self::never())->method('update');
        $mapper = $this->createStub(ServerEmailRequestMapper::class);
        $mapper->method('mapUpdate')->willReturn(['smtpHost' => 'changed', 'smtpPassword' => 'secret']);
        $controller = new ServerEmailController($this->createStub(ServerSettings::class), $service, $mapper);

        $response = $controller->update($this->createStub(Request::class));
        self::assertSame(409, $response->getStatusCode()->getCode());
        self::assertStringNotContainsString('secret', (string)$response->getBody());
    }

    public function testDeleteReturnsConflictFromService() : void
    {
        $service = $this->createMock(ServerEmailSettingsService::class);
        $service->expects(self::once())->method('delete')->willThrowException(new EmailSettingsConflict('Blocked.'));
        $controller = new ServerEmailController(
            $this->createStub(ServerSettings::class), $service, $this->createStub(ServerEmailRequestMapper::class),
        );

        self::assertSame(409, $controller->delete()->getStatusCode()->getCode());
    }
}
