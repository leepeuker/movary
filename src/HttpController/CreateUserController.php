<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Throwable;

class CreateUserController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function createUser(Request $request) : Response
    {
        if ($this->userApi->hasUsers() === true) {
            return Response::createFoundRedirect('/');
        }

        $postParameters = $request->getPostParameters();

        $email = isset($postParameters['email']) === false ? null : (string)$postParameters['email'];
        $name = isset($postParameters['name']) === false ? null : (string)$postParameters['name'];
        $password = isset($postParameters['password']) === false ? null : (string)$postParameters['password'];
        $repeatPassword = isset($postParameters['password']) === false ? null : (string)$postParameters['repeatPassword'];

        if ($email === null || $name === null || $password === null || $repeatPassword === null) {
            $this->sessionWrapper->set('missingFormData', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        if ($password !== $repeatPassword) {
            $this->sessionWrapper->set('errorPasswordNotEqual', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        try {
            $this->userApi->createUser($email, $password, $name);

            $this->authenticationService->login($email, $password, false);
        } catch (PasswordTooShort $e) {
            $this->sessionWrapper->set('errorPasswordTooShort', true);
        } catch (UsernameInvalidFormat $e) {
            $this->sessionWrapper->set('errorUsernameInvalidFormat', true);
        } catch (Throwable $t) {
            $this->sessionWrapper->set('errorGeneric', true);
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }
}
