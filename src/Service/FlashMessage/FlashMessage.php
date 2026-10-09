<?php declare(strict_types=1);

namespace Movary\Service\FlashMessage;

enum FlashMessage : string
{
    case CREATE_USER_EMAIL_NOT_UNIQUE = 'create-user-email-not-unique';

    case CREATE_USER_GENERIC_ERROR = 'create-user-generic-error';

    case CREATE_USER_MISSING_FORM_DATA = 'create-user-missing-form-data';

    case CREATE_USER_PASSWORDS_NOT_EQUAL = 'create-user-passwords-not-equal';

    case CREATE_USER_PASSWORD_TOO_SHORT = 'create-user-password-too-short';

    case CREATE_USER_USERNAME_INVALID = 'create-user-username-invalid';

    case CREATE_USER_USERNAME_NOT_UNIQUE = 'create-user-username-not-unique';

    case DASHBOARD_ROWS_RESET = 'dashboard-rows-reset';

    case IMPORT_HISTORY_FAILED = 'import-history-failed';

    case IMPORT_HISTORY_SUCCESSFUL = 'import-history-successful';

    case IMPORT_RATINGS_FAILED = 'import-ratings-failed';

    case IMPORT_RATINGS_SUCCESSFUL = 'import-ratings-successful';

    case IMPORT_WATCHLIST_FAILED = 'import-watchlist-failed';

    case IMPORT_WATCHLIST_SUCCESSFUL = 'import-watchlist-successful';

    case LETTERBOXD_DIARY_FILE_INVALID = 'letterboxd-diary-file-invalid';

    case LETTERBOXD_DIARY_SYNC_SCHEDULED = 'letterboxd-diary-sync-scheduled';

    case LETTERBOXD_RATINGS_FILE_INVALID = 'letterboxd-ratings-file-invalid';

    case LETTERBOXD_RATINGS_SYNC_SCHEDULED = 'letterboxd-ratings-sync-scheduled';

    case MASTODON_CREDENTIALS_UPDATED = 'mastodon-credentials-updated';

    case PASSWORD_RESET_REQUESTED = 'password-reset-requested';

    case TRAKT_CREDENTIALS_UPDATED = 'trakt-credentials-updated';

    case TRAKT_HISTORY_IMPORT_SCHEDULED = 'trakt-history-import-scheduled';

    case TRAKT_RATINGS_IMPORT_SCHEDULED = 'trakt-ratings-import-scheduled';

    case TWO_FACTOR_AUTHENTICATION_DISABLED = 'two-factor-authentication-disabled';

    case TWO_FACTOR_AUTHENTICATION_ENABLED = 'two-factor-authentication-enabled';

    public function getDestination() : FlashMessageDestination
    {
        return match ($this) {
            self::CREATE_USER_EMAIL_NOT_UNIQUE,
            self::CREATE_USER_GENERIC_ERROR,
            self::CREATE_USER_MISSING_FORM_DATA,
            self::CREATE_USER_PASSWORDS_NOT_EQUAL,
            self::CREATE_USER_PASSWORD_TOO_SHORT,
            self::CREATE_USER_USERNAME_INVALID,
            self::CREATE_USER_USERNAME_NOT_UNIQUE => FlashMessageDestination::CREATE_USER,
            self::DASHBOARD_ROWS_RESET => FlashMessageDestination::ACCOUNT_DASHBOARD,
            self::IMPORT_HISTORY_FAILED,
            self::IMPORT_HISTORY_SUCCESSFUL,
            self::IMPORT_RATINGS_FAILED,
            self::IMPORT_RATINGS_SUCCESSFUL,
            self::IMPORT_WATCHLIST_FAILED,
            self::IMPORT_WATCHLIST_SUCCESSFUL => FlashMessageDestination::ACCOUNT_DATA,
            self::LETTERBOXD_DIARY_FILE_INVALID,
            self::LETTERBOXD_DIARY_SYNC_SCHEDULED,
            self::LETTERBOXD_RATINGS_FILE_INVALID,
            self::LETTERBOXD_RATINGS_SYNC_SCHEDULED => FlashMessageDestination::LETTERBOXD,
            self::MASTODON_CREDENTIALS_UPDATED => FlashMessageDestination::MASTODON,
            self::PASSWORD_RESET_REQUESTED => FlashMessageDestination::PASSWORD_RESET_REQUEST,
            self::TRAKT_CREDENTIALS_UPDATED,
            self::TRAKT_HISTORY_IMPORT_SCHEDULED,
            self::TRAKT_RATINGS_IMPORT_SCHEDULED => FlashMessageDestination::TRAKT,
            self::TWO_FACTOR_AUTHENTICATION_DISABLED,
            self::TWO_FACTOR_AUTHENTICATION_ENABLED => FlashMessageDestination::ACCOUNT_SECURITY,
        };
    }
}
