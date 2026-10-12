## Webhook (Scrobbler)

### Description

Automatically add new [Plex](https://www.plex.tv/) movie plays and ratings to Movary.

!!! Info

    To use the required webhooks feature in Plex an active [Plex Pass](https://www.plex.tv/plex-pass/) subscription is neceessary.

### Instruction
- Generate a webhook url in Movary for your user on the Plex integration settings page (`/settings/integrations/plex`)
- Add the generated url as a [webhook to your Plex server](https://support.plex.tv/articles/115002267687-webhooks/) to start scrobbling

You can select what you want to scrobble (movie plays and/or ratings) via the "Scrobble Options" checkboxes on the settings page.

!!! tip

    Keep your webhook url private to prevent abuse.

## Authentication

Some features require access to protected personal Plex data.
You can authenticate Movary against Plex on the Plex integration settings page (`/settings/integrations/plex`).

!!! Info

    Requires the server configuration [PLEX_IDENTIFIER](../configuration.md#third-party-integrations) to be set.

During the authentication process a Plex access token is generated and stored in Movary. 
This token will be used in all further Plex API requests.
When an authentication is removed from Movary, the token will be deleted only in Movary.

!!! Info

    Removing the authentication only deletes the token stored in Movary itself. The token still exists in Plex.
    To invalidate the access token in Plex, go to your Plex settings at: Account -> Authorized devices -> Click on the red cross for the entry "Movary"

## URL Validation and SSRF Protection

### Overview

Movary validates Plex server URLs to protect against Server-Side Request Forgery (SSRF) attacks.

### Security Features

URL validation:

- allows only HTTP and HTTPS on ports 80, 443, 8096, 8920, and 32400
- checks every resolved IPv4 and IPv6 address
- blocks private, reserved, loopback, and cloud metadata destinations by default
- pins the validated address to the connection to prevent DNS rebinding
- disables redirects so they cannot bypass destination validation

### Configuration

Protection is always enabled. To use a private Plex server, an administrator must add its exact hostname, IP address, or network in CIDR notation to `PLEX_URL_ALLOWLIST`:

```dotenv
PLEX_URL_ALLOWLIST=plex.home,192.168.1.20,fd00::/64
```

!!! warning

    Add only destinations that Movary should be allowed to contact. A hostname entry is an exact match; IP and CIDR entries authorize only matching addresses.

See [Third party integrations](../configuration.md#third-party-integrations) for all related settings.

### Recommendations

- Prefer a single exact hostname or IP address over a broad network range.
- Keep the allowlist empty when the Plex server has a public destination.

## Watchlist import

### Description

Import missing movies from your Plex Watchlist to your Movary Watchlist.
Missing movies imported to the Movary Watchlist are put at the beginning of the list in the same order as they are in Plex.

!!! Info

    Plex [authentication](#authentication) is required.

### Instruction

#### Web UI
You can schedule import jobs and see the status/history of past jobs on the Plex integration settings page (`/settings/integrations/plex`).

#### Command
You can directly trigger an import via CLI

```shell
php bin/console.php plex:watchlist:import --userId=<id>
```

!!! tip

    You could create a cronjob to regularly import your watchlist to keep up to date automatically. 
