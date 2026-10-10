# API authentication

Movary's API accepts personal API tokens for trusted clients such as scripts,
CLI tools, home automation, and self-hosted integrations. A token has the same
API permissions as the user who created it.

Create and manage tokens under **Settings → Account → API tokens**. The complete
token is displayed only once. Movary stores a one-way hash and subsequently
shows only a non-secret prefix, creation time, last-used time, and expiration.

Always transmit personal tokens over HTTPS. Anyone possessing a token can use
it until it expires or is revoked.

## Authentication headers

Bearer authentication is supported:

```bash
curl --fail-with-body \
  --header "Authorization: Bearer $MOVARY_TOKEN" \
  https://movary.example/api/authentication/token
```

The custom header remains supported permanently:

```bash
curl --fail-with-body \
  --header "X-Movary-Token: $MOVARY_TOKEN" \
  https://movary.example/api/authentication/token
```

Do not send both forms in one request. Movary rejects a request containing both
a Bearer credential and `X-Movary-Token` with `401 Unauthorized`.

## Reverse proxies using Basic authentication

HTTP has only one `Authorization` header for credentials. If a reverse proxy
uses that header for Basic authentication, send the Movary token through
`X-Movary-Token`. Movary ignores non-Bearer authorization schemes when looking
for a personal token, so the following combination remains supported:

```http
Authorization: Basic <proxy-credentials>
X-Movary-Token: <personal-token>
```

Missing, malformed, expired, revoked, and unknown personal credentials receive
the same `401 Unauthorized` response. A successfully authenticated user who is
not permitted to access a resource receives `403 Forbidden`.
