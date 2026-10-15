<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\Service\EmailVerificationTokenService;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Environment;

class EmailVerificationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EmailVerificationRequestService $requestService,
        private readonly EmailVerificationTokenService $tokenService,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly LoggerInterface $logger,
        private readonly Authentication $authentication,
    ) {
    }

    public function renderVerificationPage(Request $request) : Response
    {
        $token = $this->getStringParameter($request->getGetParameters(), 'token');

        return $this->renderVerificationForm($token, $this->isTokenValid($token));
    }

    public function verifyEmail(Request $request) : Response
    {
        $token = $this->getStringParameter($request->getPostParameters(), 'token');
        $userId = $token === '' ? null : $this->tokenService->findUserIdByToken($token);
        if ($userId === null) {
            return $this->renderVerificationForm('', false);
        }

        $authenticatedUser = $this->authentication->authenticateWebSession();

        try {
            if ($this->tokenService->verifyEmail($token) === false) {
                return $this->renderVerificationForm('', false);
            }
        } catch (Throwable $exception) {
            $this->logger->error('Could not verify email address.', ['exception' => $exception]);

            return $this->renderVerificationForm($token, true, true);
        }

        if ($authenticatedUser?->getUserId() === $userId) {
            $this->authentication->logout();
        }

        return Response::createSensitiveSeeOther(
            $this->applicationUrlService->createApplicationUrl(
                RelativeUrl::create('/login?email-verification=success'),
            ),
            [Header::createReferrerPolicyNoReferrer()],
        );
    }

    public function resend(Request $request) : Response
    {
        $email = $this->getStringParameter($request->getPostParameters(), 'email');
        $this->requestService->request($email);

        return Response::createSensitive(
            StatusCode::createOk(),
            $this->twig->render('page/email-verification-requested.html.twig'),
            [Header::createReferrerPolicyNoReferrer()],
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

    private function renderVerificationForm(string $token, bool $tokenValid, bool $genericError = false) : Response
    {
        return Response::createSensitive(
            StatusCode::createOk(),
            $this->twig->render(
                'page/email-verification.html.twig',
                [
                    'token' => $token,
                    'tokenValid' => $tokenValid,
                    'genericError' => $genericError,
                ],
            ),
            [Header::createReferrerPolicyNoReferrer()],
        );
    }
}
