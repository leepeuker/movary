<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\ValueObject\RelativeUrl;

class ApplicationUrlService
{
    public function __construct(
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function createApplicationUrl(?RelativeUrl $relativeUrl = null) : string
    {
        $customApplicationUrl = $this->serverSettings->getApplicationUrl();
        if ($customApplicationUrl === null) {
            if ($relativeUrl === null) {
                return  '/';
            }

            return  rtrim((string)$relativeUrl, '/');
        }

        return rtrim($customApplicationUrl, '/') . rtrim((string)$relativeUrl, '/');
    }

    // create e.g., "movary.test" from "https://movary.test"
    public function createApplicationDomain(): string
    {
        $application_url = $this->createApplicationUrl();
        $domain = (string)preg_replace("/(^https?:\/\/|\/?$)/", "", $application_url);
        return $domain;
    }

    public function hasApplicationUrl() : bool
    {
        return $this->serverSettings->getApplicationUrl() !== null;
    }
}
