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
            return $relativeUrl === null ? '' : (string)$relativeUrl;
        }

        return trim($customApplicationUrl, '/') . $relativeUrl;
    }

    public function hasApplicationUrl() : bool
    {
        return $this->serverSettings->getApplicationUrl() !== null;
    }
}
