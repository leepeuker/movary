<?php declare(strict_types=1);

namespace Movary\Domain\User\ValueObject;

enum CredentialType
{
    case API_TOKEN;

    case WEB_SESSION;
}
