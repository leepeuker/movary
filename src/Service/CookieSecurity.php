<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\ValueObject\Http\Request;

class CookieSecurity
{
    public function __construct(
        private readonly Request $request,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function isSecure() : bool
    {
        if ($this->request->isHttps() === true) {
            return true;
        }

        return str_starts_with(strtolower($this->serverSettings->getApplicationUrl() ?? ''), 'https://');
    }
}
