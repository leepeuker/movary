# Issue #23 investigation: protect login against brute-force attacks

Issue: [#23 Protect login against brute-force attacks](https://github.com/leepeuker/movary/issues/23)

## Implementation status

Implemented with a database-backed limiter, a five-attempt account limit and a twenty-attempt source-IP limit over a 15-minute window. Throttled requests receive `429 Too Many Requests` and a `Retry-After` header. The client IP is taken only from `REMOTE_ADDR`; forwarded headers are deliberately not trusted.

## Findings

The issue is valid for the current codebase: failed password and TOTP attempts are not rate-limited.

All normal sign-ins use one endpoint and service flow:

```text
public/js/login.js
  -> POST /api/authentication/token
  -> Api/AuthenticationController::createToken()
  -> Authentication::login()
  -> Authentication::findUserAndVerifyAuthentication()
```

External API clients use the same `POST /api/authentication/token` endpoint, so a service-level solution protects both the browser and API clients. Password verification and login-time TOTP verification are performed in `Authentication::findUserAndVerifyAuthentication()`.

`TwoFactorAuthenticationApi::verifyTotpUri()` has one additional use: confirming a newly configured TOTP secret in the authenticated account-security page. That operation should not be included in an unauthenticated-login throttle; it is not an account takeover entry point and has different user-experience requirements.

The cookie part of the issue has already been addressed on the current branch. `Authentication::setAuthenticationCookieAndNewSession()` and `clearAuthenticationCookie()` now set `secure` when either the request is HTTPS or the configured application URL is HTTPS. Unit tests cover both cases.

## Recommended approach

Add a small database-backed `LoginAttemptLimiter` domain service, called by `Authentication::login()` / `findUserAndVerifyAuthentication()`.

Use a database-backed limiter rather than PHP memory or session state. It works across PHP workers and containers, survives restarts, and is compatible with both supported database engines.

Suggested initial policy (the exact numbers should be approved before implementation):

| Scope | Suggested limit | Purpose |
| --- | --- | --- |
| Account identifier | 5 failed attempts in 15 minutes, then a 15-minute lockout | Limits targeted password and TOTP guessing. |
| Source IP | 20 failed attempts in 15 minutes | Limits password spraying and attempts against unknown accounts. |

The limiter should:

1. Check both scopes before an authentication attempt.
2. Record failures for every invalid password, unknown email, missing TOTP code, and invalid TOTP code.
3. Reset the account-identifier failure state only after complete authentication succeeds (password plus TOTP, where enabled).
4. Retain enough expiry data to calculate when a block ends, and periodically remove expired rows.
5. Use a stable hash of normalized email addresses and IP addresses in storage, rather than retaining those values in plaintext.

## Required plumbing

The `Request` value object now exposes a narrowly scoped `getClientIp()` value derived from `REMOTE_ADDR`.

Do not trust `X-Forwarded-For` by default: clients can forge it. Supporting deployments behind a reverse proxy needs an explicit trusted-proxy configuration and a documented parsing rule. A simpler first change could ship account-identifier throttling only, while operators enforce IP throttling at their reverse proxy; this leaves an account-lockout denial-of-service tradeoff.

## Persistence design

The implementation adds a schema migration for the limiter table.

One possible schema is a `user_login_attempt_limit` table with separate entries per scope:

```text
scope             -- 'account' or 'ip'
subject_hash      -- SHA-256 hash of normalized email or IP
failure_count
window_started_at
locked_until      -- nullable
updated_at
primary key (scope, subject_hash)
```

The implementation stores one row per failed attempt and queries the oldest attempt still within the limit window. SQL is implemented and verified on both SQLite and MySQL.

## HTTP and UI behaviour

For a throttled request, return HTTP `429 Too Many Requests`; add `StatusCode::createTooManyRequests()` and a `Retry-After` header helper. The browser login script should render a clear retry message.

There is an important privacy choice: an account-specific `429` can make it easier to confirm that an email belongs to a locked account. To avoid worsening account enumeration, use the same generic response body for invalid and throttled sign-ins, or only expose a retry countdown for IP-wide throttling. This needs product/security confirmation.

The existing API already returns a generic `InvalidCredentials` response for unknown email and incorrect password. Preserve that behaviour.

## Changed files and tests

The change adds a login-attempt limiter and repository operations under `src/Domain/User/`, calls it from `Authentication`, captures the client IP in `Request`, and adds the `429` response, browser handling, OpenAPI documentation, and a Doctrine migration. Unit tests cover the limiter, authentication outcomes, repository access, and the API response. The repository migration verification covers SQLite and MySQL.

Run the required project checks after implementation:

```bash
composer test-cs
composer test-phpstan
composer test-psalm
composer test-unit
```

## Follow-up consideration

An account-specific `429` can make it easier to confirm that an email belongs to a previously locked account. The implementation preserves the generic invalid-credentials JSON body, but the status and `Retry-After` header remain observable. If that tradeoff is unacceptable, a future revision can return a generic `401` for account-only blocks while retaining `429` for IP-wide throttling.
