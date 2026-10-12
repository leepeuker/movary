## Webhook (Scrobbler)

### Description

Automatically add new [Jellyin](https://jellyfin.org/) movie plays to Movary.

!!! Info

    Requires the [webhook plugin](https://github.com/jellyfin/jellyfin-plugin-webhook) to be installed and active in Jellyfin.

### Instruction

- Generate a webhook url in Movary for your user on the Jellyfin integration settings page (`/settings/integrations/jellyfin`)
- Add the generated url in the Jellyfin webhook plugin as a `Generic Destination` and activate only:
    - Notification Type => "Playback Stop"
    - User Filter => Choose your user
    - Item Type => "Movies" + "Send All Properties (ignores template)"

!!! tip

    Keep your webhook url private to prevent abuse.

## Authentication

Some features require access to protected personal Jellyfin data.
You can authenticate Movary against Jellyfin on the Jellyfin integration settings page (`/settings/integrations/jellyfin`).

!!! Info

    Requires the server configuration [JELLYFIN_DEVICE_ID](../configuration.md#third-party-integrations) to be set.

During the authentication process a Jellyfin access token is generated and stored in Movary.
This token will be used in all further Jellyfin API requests.
When an authentication is removed from Movary, the token will be deleted in Movary and the Jellyfin server.

## URL Validation and SSRF Protection

### Overview

Movary validates Jellyfin server URLs to protect against Server-Side Request Forgery (SSRF) attacks.

### Security Features

URL validation:

- allows only HTTP and HTTPS on ports 80, 443, 8096, 8920, and 32400
- checks every resolved IPv4 and IPv6 address
- blocks private, reserved, loopback, and cloud metadata destinations by default
- pins the validated address to the connection to prevent DNS rebinding
- disables redirects so they cannot bypass destination validation

### Configuration

Protection is always enabled. To use a private Jellyfin server, an administrator must add its exact hostname, IP address, or network in CIDR notation to `JELLYFIN_URL_ALLOWLIST`:

```dotenv
JELLYFIN_URL_ALLOWLIST=jellyfin.home,192.168.1.10,fd00::/64
```

!!! warning

    Add only destinations that Movary should be allowed to contact. A hostname entry is an exact match; IP and CIDR entries authorize only matching addresses.

See [Third party integrations](../configuration.md#third-party-integrations) for all related settings.

### Recommendations

- Prefer a single exact hostname or IP address over a broad network range.
- Keep the allowlist empty when the Jellyfin server has a public destination.

## Sync

General notes:

- Movies are matched via tmdb id. Movies without a tmdb id in Jellyfin are ignored.
- Movies will be updated in all Jellyfin libraries.
- Backup your Jellyfin database regularly in case something goes wrong!

### Automatic sync

You can keep your Jellyfin libraries automatically up to date with your latest Movary watch history changes.

!!! Info

    Jellyfin [authentication](#authentication) is required.  

If the automatic sync is enabled (e.g. on `/settings/integrations/jellyfin`) new watch dates added to Movary are automatically pushed as plays to Jellyfin.
If a movie has its last watch date removed in Movary it is set to unwatched in Jellyfin.

### Export

#### Description

You can export your Movary watch dates as plays to Jellyfin.

!!! Info

    Jellyfin [authentication](#authentication) is required.  

Movary will compare its movie watch dates against the Jellyfin movie plays.
Movies not marked as watched in Jellyfin but with watch dates in Movary are marked as watched and get the latest watch date set as the last play date.
Movies already marked as watched are updated with the latest watch date as the last play date if the dates are not the same.

#### CLI command

```shell
php bin/console.php jellyfin:export <userId>
```

### Import

#### Description

You can import your Jellyfin plays as Movary watch dates.

!!! Info

    Jellyfin [authentication](#authentication) is required.  

Movary will compare the Jellyfin movie plays against its movie watch dates.
Movies with multiple plays in Jellyfin are handled as one watch date, using the date of the latest play.
Watch dates are added to Movary if they are missing, existing watch dates are not changed. 

#### CLI command

```shell
php bin/console.php jellyfin:import <userId>
```

