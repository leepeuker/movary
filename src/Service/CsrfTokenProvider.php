<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Request;

class CsrfTokenProvider
{
    public const string COOKIE_NAME = 'csrf';

    private ?string $token = null;

    public function __construct(
        private readonly CsrfTokenService $tokenService,
        private readonly Request $request,
        private readonly CookieSecurity $cookieSecurity,
    ) {
    }

    public function getToken() : string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $authenticationToken = $this->request->getCookie(Authentication::AUTHENTICATION_COOKIE_NAME);
        $cookieToken = $this->request->getCookie(self::COOKIE_NAME);

        if ($cookieToken !== null
            && $this->tokenService->isValid($cookieToken, $cookieToken, $authenticationToken) === true
        ) {
            $this->token = $cookieToken;

            return $this->token;
        }

        $this->token = $this->tokenService->create($authenticationToken);
        setcookie(
            self::COOKIE_NAME,
            $this->token,
            [
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $this->cookieSecurity->isSecure(),
            ],
        );

        return $this->token;
    }
}
