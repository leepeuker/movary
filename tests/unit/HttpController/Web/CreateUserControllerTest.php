<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\CreateUserController;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Twig\Environment;

#[CoversClass(CreateUserController::class)]
#[AllowMockObjectsWithoutExpectations]
class CreateUserControllerTest extends TestCase
{
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private Authentication|MockObject $authenticationMock;

    private FlashMessageService|MockObject $flashMessageServiceMock;

    private CreateUserController $subject;

    private Environment|MockObject $twigMock;

    private UserApi|MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = new CreateUserController(
            $this->twigMock,
            $this->authenticationMock,
            $this->userApiMock,
            $this->flashMessageServiceMock,
            $this->applicationUrlServiceMock,
        );
    }

    public function testCreateUserAddsMissingFormDataMessage() : void
    {
        $request = $this->createRequest([
            'name' => 'user',
            'password' => 'password',
            'repeatPassword' => 'password',
        ]);
        $this->userApiMock->expects(self::never())->method('createUser');
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::CREATE_USER_MISSING_FORM_DATA);
        $this->expectCreateUserRedirect();

        $response = $this->subject->createUser($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary/create-user', (string)$response->getHeaders()[0]);
    }

    public function testCreateUserAddsPasswordsNotEqualMessage() : void
    {
        $request = $this->createRequest($this->createValidParameters('different-password'));
        $this->userApiMock->expects(self::never())->method('createUser');
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::CREATE_USER_PASSWORDS_NOT_EQUAL);
        $this->expectCreateUserRedirect();

        $response = $this->subject->createUser($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary/create-user', (string)$response->getHeaders()[0]);
    }

    #[DataProvider('provideValidationErrors')]
    public function testCreateUserAddsValidationMessage(Throwable $exception, FlashMessage $message) : void
    {
        $request = $this->createRequest($this->createValidParameters());
        $this->userApiMock->method('hasUsers')->willReturn(true);
        $this->userApiMock
            ->expects(self::once())
            ->method('createUser')
            ->with('user@example.com', 'password', 'user', false)
            ->willThrowException($exception);
        $this->authenticationMock->expects(self::never())->method('login');
        $this->flashMessageServiceMock->expects(self::once())->method('add')->with($message);
        $this->applicationUrlServiceMock->method('createApplicationUrl')->willReturn('/movary');

        $response = $this->subject->createUser($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary', (string)$response->getHeaders()[0]);
    }

    public function testCreateUserAddsGenericError() : void
    {
        $request = $this->createRequest($this->createValidParameters());
        $this->userApiMock->method('createUser')->willThrowException(new RuntimeException('unexpected'));
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::CREATE_USER_GENERIC_ERROR);
        $this->applicationUrlServiceMock->method('createApplicationUrl')->willReturn('/movary');

        $response = $this->subject->createUser($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary', (string)$response->getHeaders()[0]);
    }

    #[DataProvider('provideRenderedMessages')]
    public function testRenderPageConsumesMessageOnce(FlashMessage $message, string $variable) : void
    {
        $messageConsumed = false;
        $this->userApiMock->method('hasUsers')->willReturn(false);
        $this->flashMessageServiceMock
            ->expects(self::exactly(14))
            ->method('consume')
            ->willReturnCallback(static function (FlashMessage $candidate) use ($message, &$messageConsumed) : bool {
                if ($candidate !== $message || $messageConsumed === true) {
                    return false;
                }

                $messageConsumed = true;

                return true;
            });

        $renderCount = 0;
        $this->twigMock
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template, array $data) use ($variable, &$renderCount) : string {
                self::assertSame('page/create-user.html.twig', $template);
                self::assertSame('Create initial admin user', $data['subtitle']);
                self::assertSame($renderCount === 0, $data[$variable]);

                foreach (self::getRenderedVariables() as $renderedVariable) {
                    if ($renderedVariable !== $variable) {
                        self::assertFalse($data[$renderedVariable]);
                    }
                }

                $renderCount++;

                return 'create user';
            });

        $firstResponse = $this->subject->renderPage();
        $secondResponse = $this->subject->renderPage();

        self::assertSame('create user', $firstResponse->getBody());
        self::assertSame('create user', $secondResponse->getBody());
    }

    /** @return iterable<string, array{Throwable, FlashMessage}> */
    public static function provideValidationErrors() : iterable
    {
        yield 'password too short' => [
            new PasswordTooShort(8),
            FlashMessage::CREATE_USER_PASSWORD_TOO_SHORT,
        ];
        yield 'username invalid' => [
            UsernameInvalidFormat::create(),
            FlashMessage::CREATE_USER_USERNAME_INVALID,
        ];
        yield 'username not unique' => [
            UsernameNotUnique::create(),
            FlashMessage::CREATE_USER_USERNAME_NOT_UNIQUE,
        ];
        yield 'email not unique' => [
            EmailNotUnique::create(),
            FlashMessage::CREATE_USER_EMAIL_NOT_UNIQUE,
        ];
    }

    /** @return iterable<string, array{FlashMessage, string}> */
    public static function provideRenderedMessages() : iterable
    {
        yield 'password too short' => [
            FlashMessage::CREATE_USER_PASSWORD_TOO_SHORT,
            'errorPasswordTooShort',
        ];
        yield 'passwords not equal' => [
            FlashMessage::CREATE_USER_PASSWORDS_NOT_EQUAL,
            'errorPasswordNotEqual',
        ];
        yield 'username invalid' => [
            FlashMessage::CREATE_USER_USERNAME_INVALID,
            'errorUsernameInvalidFormat',
        ];
        yield 'username not unique' => [
            FlashMessage::CREATE_USER_USERNAME_NOT_UNIQUE,
            'errorUsernameUnique',
        ];
        yield 'email not unique' => [
            FlashMessage::CREATE_USER_EMAIL_NOT_UNIQUE,
            'errorEmailUnique',
        ];
        yield 'generic error' => [
            FlashMessage::CREATE_USER_GENERIC_ERROR,
            'errorGeneric',
        ];
        yield 'missing form data' => [
            FlashMessage::CREATE_USER_MISSING_FORM_DATA,
            'missingFormData',
        ];
    }

    /** @return array<string, string> */
    private function createValidParameters(string $repeatPassword = 'password') : array
    {
        return [
            'email' => 'user@example.com',
            'name' => 'user',
            'password' => 'password',
            'repeatPassword' => $repeatPassword,
        ];
    }

    /** @param array<string, string> $parameters */
    private function createRequest(array $parameters) : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn($parameters);
        $request->method('getUserAgent')->willReturn('user agent');

        return $request;
    }

    private function expectCreateUserRedirect() : void
    {
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(static fn(RelativeUrl $url) => (string)$url === '/create-user'))
            ->willReturn('/movary/create-user');
    }

    /** @return array<string> */
    private static function getRenderedVariables() : array
    {
        return [
            'errorPasswordTooShort',
            'errorPasswordNotEqual',
            'errorUsernameInvalidFormat',
            'errorUsernameUnique',
            'errorEmailUnique',
            'errorGeneric',
            'missingFormData',
        ];
    }
}
