<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Throwable;
use Twig\Environment;

class CreateUserController
{
    public const string MOVARY_WEB_CLIENT = 'Movary Web';

    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly SessionWrapper $sessionWrapper,
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
            $this->userApi->createUser($email, $password, $name, $hasUsers === false);

            $this->authenticationService->login($email, $password, false, self::MOVARY_WEB_CLIENT, $userAgent);
        } catch (PasswordTooShort) {
            $this->sessionWrapper->set('errorPasswordTooShort', true);
        } catch (UsernameInvalidFormat) {
            $this->sessionWrapper->set('errorUsernameInvalidFormat', true);
        } catch (UsernameNotUnique) {
            $this->sessionWrapper->set('errorUsernameUnique', true);
        } catch (EmailNotUnique) {
            $this->sessionWrapper->set('errorEmailUnique', true);
        } catch (Throwable) {
            $this->sessionWrapper->set('errorGeneric', true);
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function renderPage() : Response
    {
        $hasUsers = $this->userApi->hasUsers();

        $errorPasswordTooShort = $this->sessionWrapper->find('errorPasswordTooShort');
        $errorPasswordNotEqual = $this->sessionWrapper->find('errorPasswordNotEqual');
        $errorUsernameInvalidFormat = $this->sessionWrapper->find('errorUsernameInvalidFormat');
        $errorUsernameUnique = $this->sessionWrapper->find('errorUsernameUnique');
        $errorEmailUnique = $this->sessionWrapper->find('errorEmailUnique');
        $missingFormData = $this->sessionWrapper->find('missingFormData');
        $errorGeneric = $this->sessionWrapper->find('errorGeneric');

        $this->sessionWrapper->unset(
            'errorPasswordTooShort',
            'errorPasswordNotEqual',
            'errorUsernameInvalidFormat',
            'errorUsernameUnique',
            'errorEmailUnique',
            'errorGeneric',
            'missingFormData',
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/create-user.html.twig', [
                'subtitle' => $hasUsers === false ? 'Create initial admin user' : 'Create new user',
                'errorPasswordTooShort' => $errorPasswordTooShort,
                'errorPasswordNotEqual' => $errorPasswordNotEqual,
                'errorUsernameInvalidFormat' => $errorUsernameInvalidFormat,
                'errorUsernameUnique' => $errorUsernameUnique,
                'errorEmailUnique' => $errorEmailUnique,
                'errorGeneric' => $errorGeneric,
                'missingFormData' => $missingFormData
            ]),
        );
    }
}
