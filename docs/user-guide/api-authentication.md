# API authentication

Movary's API accepts personal API tokens for trusted clients such as scripts, CLI tools, home automation, and self-hosted integrations. A token has the same API permissions as the user who created it.

Client API routes use the `/api/v1` prefix. The previous unversioned `/api` routes remain available
for compatibility until Movary 1.0.

## Create a token

Open **Settings → Account → API tokens**, select **Add token**, and enter a descriptive name. Tokens can expire after 30, 90, or 365 days, or never expire.

Copy the complete token immediately after creating it. Movary displays it only once and stores a one-way hash. The token list subsequently shows only a non-secret prefix, creation time, last-used time, and expiration.

Always transmit personal tokens over HTTPS and store them like passwords. Anyone possessing a token can use it until it expires or is revoked.

## Revoke tokens

Select a token row under **Settings → Account → API tokens** to review its details or revoke it. The delete action above the table revokes every personal API token for the account.

Revocation takes effect immediately. Any client using a revoked token must be configured with a newly created token before it can access the API again.

A normal password change leaves personal API tokens active. A password reset made through **Forgot password?** revokes them as a security precaution. See [Account and security](account-and-security.md) for the complete password workflows.

## Authentication headers

Bearer authentication is supported:

```bash
curl --fail-with-body \
  --header "Authorization: Bearer $MOVARY_TOKEN" \
  https://movary.example/api/v1/me
```

The custom header remains supported permanently:

```bash
curl --fail-with-body \
  --header "X-Movary-Token: $MOVARY_TOKEN" \
  https://movary.example/api/v1/me
```

Do not send both forms in one request. Movary rejects a request containing both a Bearer credential and `X-Movary-Token` with `401 Unauthorized`.

## Reverse proxies using Basic authentication

HTTP has only one `Authorization` header for credentials. If a reverse proxy uses that header for Basic authentication, send the Movary token through `X-Movary-Token`. Movary ignores non-Bearer authorization schemes when looking for a personal token, so the following combination remains supported:

```http
Authorization: Basic <proxy-credentials>
X-Movary-Token: <personal-token>
```

Missing, malformed, expired, revoked, and unknown personal credentials receive the same `401 Unauthorized` response. A successfully authenticated user who is not permitted to access a resource receives `403 Forbidden`.

See the [OpenAPI specification](../openapi.json) for the available endpoints and request formats.
