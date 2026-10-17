# Jellyfin

The Jellyfin integration can scrobble completed movie plays into Movary. With account authentication, it can also import or export watch history and automatically export later history changes.

Movies are matched by TMDB identifier. Jellyfin movies without one are ignored by scrobbling and synchronization.

## Scrobble completed plays

Scrobbling requires Jellyfin's [Webhook plugin](https://jellyfin.org/docs/general/server/notifications/) to be installed and active.

1. Open **Settings → Integrations → Jellyfin** in Movary.
2. Select **Regenerate url** to create a personal webhook URL.
3. Enable **Scrobble completed watches** and select **Save**.
4. In the Jellyfin Webhook plugin, add the URL as a **Generic Destination**.
5. Enable only **Playback Stop**, select the matching Jellyfin user, select **Movies**, and enable **Send All Properties (ignores template)**.

Movary records only `PlaybackStop` notifications for movies that Jellyfin reports as played to completion.

Keep the webhook URL private. Regenerating or deleting it invalidates the previous URL, which must then be replaced or removed in Jellyfin. The public `APPLICATION_URL` must be configured before Movary can generate webhook URLs.

## Authenticate a Jellyfin account

Authentication is required for history import, export, and automatic export. An administrator must first configure `JELLYFIN_DEVICE_ID`; see [Configuration](../configuration.md#third-party-integrations).

To authenticate:

1. Enter the Jellyfin server URL under **Settings → Integrations → Jellyfin** and select **Test**.
2. Save the server URL.
3. Select **Authenticate** and enter the Jellyfin username and password.

Movary stores the returned access token, not the password. Removing authentication asks Jellyfin to invalidate the token and then removes the saved connection from Movary.

### Private Jellyfin servers

Movary allows HTTP or HTTPS destinations on ports 80, 443, 8096, 8920, and 32400. Public destinations are allowed by default. An administrator must explicitly authorize a private, reserved, or loopback destination with `JELLYFIN_URL_ALLOWLIST`, for example:

```dotenv
JELLYFIN_URL_ALLOWLIST=jellyfin.home,192.168.1.10,fd00::/64
```

Hostname entries are exact matches; IP and CIDR entries authorize matching addresses. Prefer a single hostname or IP address over a broad network range.

## Import history from Jellyfin

Select **Import history from Jellyfin** to create a background job. Movary imports the latest Jellyfin play date for each watched movie when that date is not already in Movary. Existing Movary watch dates are not changed or removed.

Select **Show imports** to review previous jobs. Administrators can alternatively run:

```shell
php bin/console.php jellyfin:import USER_ID
```

## Export history to Jellyfin

Select **Export history to Jellyfin** to create a background job. For each matching movie, Movary marks it watched in Jellyfin and sends the latest Movary watch date. Movies already using that date are skipped.

Select **Show exports** to review previous jobs. Administrators can alternatively run:

```shell
php bin/console.php jellyfin:export USER_ID
```

## Automatically export later changes

Enable **Automatically export history changes to Jellyfin** to send later Movary changes without running a full export. Adding a watch date marks the matching Jellyfin movie as watched and sends the latest date. Removing a movie's last watch date marks it unwatched in Jellyfin.

This synchronization sends changes from Movary to Jellyfin; it does not continuously import Jellyfin changes. Use the webhook for new completed plays or run the history import when existing Jellyfin data must be copied into Movary.
