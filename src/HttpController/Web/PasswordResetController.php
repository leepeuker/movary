<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageDestination;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Environment;

class PasswordResetController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly PasswordResetRequestService $requestService,
        private readonly PasswordResetTokenService $tokenService,
        private readonly FlashMessageService $flashMessageService,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function renderRequestPage() : Response
    {
        $messages = $this->flashMessageService->consumeFor(FlashMessageDestination::PASSWORD_RESET_REQUEST);
        $requestSubmitted = in_array(FlashMessage::PASSWORD_RESET_REQUESTED, $messages, true);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'page/password-reset-request.html.twig',
                ['requestSubmitted' => $requestSubmitted],
            ),
        );
    }

    public function requestReset(Request $request) : Response
    {
        $email = $this->getStringParameter($request->getPostParameters(), 'email');
        $this->requestService->request($email);
        $this->flashMessageService->add(FlashMessage::PASSWORD_RESET_REQUESTED);

        return Response::createSeeOther(
            $this->applicationUrlService->createApplicationUrl(RelativeUrl::create('/forgot-password')),
        );
    }

    public function renderResetPage(Request $request) : Response
    {
        $token = $this->getStringParameter($request->getGetParameters(), 'token');

        return $this->renderResetForm(
            $token,
            $this->isTokenValid($token),
        );
    }

    public function resetPassword(Request $request) : Response
    {
        $parameters = $request->getPostParameters();
        $token = $this->getStringParameter($parameters, 'token');
        $password = $this->getStringParameter($parameters, 'password');
        $repeatPassword = $this->getStringParameter($parameters, 'repeatPassword');

        if ($this->isTokenValid($token) === false) {
            return $this->renderResetForm('', false);
        }

        if ($password === '' || $repeatPassword === '') {
            return $this->renderResetForm($token, true, missingFormData: true);
        }

        if ($password !== $repeatPassword) {
            return $this->renderResetForm($token, true, passwordsDoNotMatch: true);
        }

        try {
            if ($this->tokenService->resetPassword($token, $password) === false) {
                return $this->renderResetForm('', false);
            }
        } catch (PasswordTooShort $exception) {
            return $this->renderResetForm($token, true, minimumPasswordLength: $exception->getMinLength());
        } catch (Throwable $exception) {
            $this->logger->error('Could not reset password.', ['exception' => $exception]);

            return $this->renderResetForm($token, true, genericError: true);
        }

        return Response::createSeeOther(
            $this->applicationUrlService->createApplicationUrl(
                RelativeUrl::create('/login?password-reset=success'),
            ),
        );
    }

    private function getStringParameter(array $parameters, string $name) : string
    {
        $value = $parameters[$name] ?? null;

        return is_string($value) === true ? $value : '';
    }

    private function isTokenValid(string $token) : bool
    {
        return $token !== '' && $this->tokenService->findUserIdByToken($token) !== null;
    }

    private function renderResetForm(
        string $token,
        bool $tokenValid,
        bool $missingFormData = false,
        bool $passwordsDoNotMatch = false,
        ?int $minimumPasswordLength = null,
        bool $genericError = false,
    ) : Response {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'page/password-reset.html.twig',
                [
                    'token' => $token,
                    'tokenValid' => $tokenValid,
                    'missingFormData' => $missingFormData,
                    'passwordsDoNotMatch' => $passwordsDoNotMatch,
                    'minimumPasswordLength' => $minimumPasswordLength,
                    'genericError' => $genericError,
                ],
            ),
        );
    }
}
