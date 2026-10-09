<?php declare(strict_types=1);

namespace Movary\Service\FlashMessage;

enum FlashMessageDestination : string
{
    case ACCOUNT_DASHBOARD = 'account-dashboard';

    case ACCOUNT_DATA = 'account-data';

    case ACCOUNT_SECURITY = 'account-security';

    case CREATE_USER = 'create-user';

    case LETTERBOXD = 'letterboxd';

    case MASTODON = 'mastodon';

    case PASSWORD_RESET_REQUEST = 'password-reset-request';

    case TRAKT = 'trakt';
}
