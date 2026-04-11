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

    Requires the server configuration [PLEX_IDENTIFIER](/configuration/#third-party-integrations) to be set.

During the authentication process a Plex access token is generated and stored in Movary. 
This token will be used in all further Plex API requests.
When an authentication is removed from Movary, the token will be deleted only in Movary.

!!! Info

    Removing the authentication only deletes the token stored in Movary itself. The token still exists in Plex.
    To invalidate the access token in Plex, go to your Plex settings at: Account -> Authorized devices -> Click on the red cross for the entry "Movary"

## URL Validation and SSRF Protection

### Overview

Movary can validate Plex server URLs to protect against Server-Side Request Forgery (SSRF) attacks.

### Security Features

When enabled, URL validation blocks:

- **Localhost access** (localhost, 127.0.0.1, ::1)
- **Private IP ranges** (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
- **Internal DNS names** (.internal, .local, .docker, .corp, .lan, .home, .priv)
- **Cloud metadata endpoints** (169.254.169.254, metadata.google.internal, etc.)
- **Suspicious ports** (only allows 80, 443, 8096, 8920)
- **DNS rebinding attacks**

### Configuration

Enable SSRF protection by setting the environment variable:

```bash
PLEX_VALIDATE_URL_SAFE=1
```

!!! warning

    Enabling this feature will break existing configurations that use:
    - Localhost Plex servers
    - Private network IP addresses
    - Internal DNS names
    
    Ensure your Plex server is accessible via a public domain name before enabling.

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
