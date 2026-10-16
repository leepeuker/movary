<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Exception\EmailNotVerified;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\LoginAttemptLimitReached;
use Movary\Domain\User\Exception\MissingTotpCode;
use Movary\Domain\User\Exception\UserDisabled;
use Movary\Domain\User\Service\Authentication;
use Movary\Service\Email\EmailSupport;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class AuthenticationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EmailSupport $emailSupport,
        private readonly bool $registrationEnabled,
        private readonly ?string $defaultEmail,
        private readonly ?string $defaultPassword,
        private readonly Authentication $authenticationService,
    ) {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function login(Request $request) : Response
    {
        $requestBody = Json::decode($request->getBody());

        if (isset($requestBody['email'], $requestBody['password']) === false) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingCredentials',
                    'message' => 'Email or password is missing'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        $totpCode = $requestBody['totpCode'] ?? null;
        if (is_string($totpCode) === false) {
            $totpCode = '';
        }
        if ($totpCode === '') {
            $totpCode = null;
        }
        $rememberMe = $requestBody['rememberMe'] ?? false;

        try {
            $this->authenticationService->loginWebSession(
                $requestBody['email'],
                $requestBody['password'],
                (bool)$rememberMe,
                $request->getUserAgent(),
                $totpCode,
            );
        } catch (LoginAttemptLimitReached $exception) {
            return Response::create(
                StatusCode::createTooManyRequests(),
                Json::encode([
                    'error' => 'InvalidCredentials',
                    'message' => 'Invalid credentials'
                ]),
                [Header::createContentTypeJson(), Header::createRetryAfter($exception->getRetryAfterSeconds())],
            );
        } catch (MissingTotpCode) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingTotpCode',
                    'message' => 'Two-factor authentication code missing'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (InvalidTotpCode) {
            return Response::createUnauthorized(
                Json::encode([
                    'error' => 'InvalidTotpCode',
                    'message' => 'Two-factor authentication code wrong'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (UserDisabled) {
            return Response::create(
                StatusCode::createForbidden(),
                Json::encode([
                    'error' => 'UserDisabled',
                    'message' => 'This account is disabled.'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (EmailNotVerified) {
            return Response::create(
                StatusCode::createForbidden(),
                Json::encode([
                    'error' => 'EmailNotVerified',
                    'message' => 'Verify your email address before signing in.'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (InvalidCredentials) {
            return Response::createUnauthorized(
                Json::encode([
                    'error' => 'InvalidCredentials',
                    'message' => 'Invalid credentials'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        return Response::createOk();
    }

    public function renderLoginPage(Request $request) : Response
    {
        $redirect = $request->getGetParameters()['redirect'] ?? false;

        $passwordChanged = false;
        if (($request->getGetParameters()['password-change'] ?? null) === 'success') {
            $passwordChanged = true;
        }
        if (($request->getGetParameters()['password-reset'] ?? null) === 'success') {
            $passwordChanged = true;
        }
        $emailVerified = ($request->getGetParameters()['email-verification'] ?? null) === 'success';
        $registrationStatus = $request->getGetParameters()['registration'] ?? null;

        $renderedTemplate = $this->twig->render(
            'page/login.html.twig',
            [
                'redirect' => $redirect,
                'passwordChanged' => $passwordChanged,
                'emailVerified' => $emailVerified,
                'registrationPendingApproval' => in_array(
                    $registrationStatus,
                    ['pending-approval', 'pending-approval-and-email-verification'],
                    true,
                ),
                'registrationEmailVerificationRequired' => in_array(
                    $registrationStatus,
                    ['email-verification-required', 'pending-approval-and-email-verification'],
                    true,
                ),
                'passwordResetAvailable' => $this->emailSupport->isPasswordResetAvailable(),
                'registrationEnabled' => $this->registrationEnabled,
                'defaultEmail' => $this->defaultEmail,
                'defaultPassword' => $this->defaultPassword,
            ],
        );

        return Response::create(
            StatusCode::createOk(),
            $renderedTemplate,
        );
    }
}
