<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use Throwable;
use Twig\Environment;

class CreateUserController
{
    public const string MOVARY_WEB_CLIENT = 'Movary Web';

    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly FlashMessageService $flashMessageService,
        private readonly ApplicationUrlService $applicationUrlService,
    ) {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createUser(Request $request) : Response
    {
        $hasUsers = $this->userApi->hasUsers();
        $postParameters = $request->getPostParameters();

        $userAgent = $request->getUserAgent();
        $email = empty($postParameters['email']) === true ? null : (string)$postParameters['email'];
        $name = empty($postParameters['name']) === true ? null : (string)$postParameters['name'];
        $password = empty($postParameters['password']) === true ? null : (string)$postParameters['password'];
        $repeatPassword = empty($postParameters['password']) === true ? null : (string)$postParameters['repeatPassword'];

        if ($email === null || $name === null || $password === null || $repeatPassword === null) {
            $this->flashMessageService->add(FlashMessage::CREATE_USER_MISSING_FORM_DATA);

            $redirectUrl = $this->applicationUrlService->createApplicationUrl(RelativeUrl::create('/create-user'));

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($redirectUrl)],
            );
        }

        if ($password !== $repeatPassword) {
            $this->flashMessageService->add(FlashMessage::CREATE_USER_PASSWORDS_NOT_EQUAL);

            $redirectUrl = $this->applicationUrlService->createApplicationUrl(RelativeUrl::create('/create-user'));

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($redirectUrl)],
            );
        }

        $flashMessage = null;
        try {
            $this->userApi->createUser($email, $password, $name, $hasUsers === false);

            $this->authenticationService->login($email, $password, false, self::MOVARY_WEB_CLIENT, $userAgent);
        } catch (PasswordTooShort) {
            $flashMessage = FlashMessage::CREATE_USER_PASSWORD_TOO_SHORT;
        } catch (UsernameInvalidFormat) {
            $flashMessage = FlashMessage::CREATE_USER_USERNAME_INVALID;
        } catch (UsernameNotUnique) {
            $flashMessage = FlashMessage::CREATE_USER_USERNAME_NOT_UNIQUE;
        } catch (EmailNotUnique) {
            $flashMessage = FlashMessage::CREATE_USER_EMAIL_NOT_UNIQUE;
        } catch (Throwable) {
            $flashMessage = FlashMessage::CREATE_USER_GENERIC_ERROR;
        }

        if ($flashMessage !== null) {
            $this->flashMessageService->add($flashMessage);
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($this->applicationUrlService->createApplicationUrl())],
        );
    }

    public function renderPage() : Response
    {
        $hasUsers = $this->userApi->hasUsers();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/create-user.html.twig', [
                'subtitle' => $hasUsers === false ? 'Create initial admin user' : 'Create new user',
                'errorPasswordTooShort' => $this->flashMessageService->consume(
                    FlashMessage::CREATE_USER_PASSWORD_TOO_SHORT,
                ),
                'errorPasswordNotEqual' => $this->flashMessageService->consume(
                    FlashMessage::CREATE_USER_PASSWORDS_NOT_EQUAL,
                ),
                'errorUsernameInvalidFormat' => $this->flashMessageService->consume(
                    FlashMessage::CREATE_USER_USERNAME_INVALID,
                ),
                'errorUsernameUnique' => $this->flashMessageService->consume(
                    FlashMessage::CREATE_USER_USERNAME_NOT_UNIQUE,
                ),
                'errorEmailUnique' => $this->flashMessageService->consume(
                    FlashMessage::CREATE_USER_EMAIL_NOT_UNIQUE,
                ),
                'errorGeneric' => $this->flashMessageService->consume(
                    FlashMessage::CREATE_USER_GENERIC_ERROR,
                ),
                'missingFormData' => $this->flashMessageService->consume(
                    FlashMessage::CREATE_USER_MISSING_FORM_DATA,
                ),
            ]),
        );
    }
}
