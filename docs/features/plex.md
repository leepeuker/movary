# Plex

The Plex integration can automatically record completed movie plays and rating changes in Movary. After authenticating a Plex account, it can also import missing movies from the Plex watchlist.

## Scrobble plays and ratings

Plex sends playback and rating events to a personal Movary webhook URL. Plex webhooks require an active [Plex Pass](https://support.plex.tv/articles/115002267687-webhooks/) for the server owner.

To configure scrobbling:

1. Open **Settings → Integrations → Plex**.
2. Select **Regenerate url** to create a webhook URL.
3. Enable **Scrobble completed watches**, **Scrobble rating updates**, or both, then select **Save**.
4. Add the generated URL under the Plex account's **Webhooks** settings.

Plex sends a `media.scrobble` event when it considers a movie watched and a `media.rate` event when its rating changes. Movary ignores other media types and events. A movie must have a TMDB identifier in Plex so Movary can match or create it.

Movary accepts Plex's multipart webhook format and validates its JSON `payload` field before
processing. Malformed payloads are rejected, and the JSON field may not exceed 256 KiB. Multipart
attachments sent by Plex are not included in that JSON limit.

New webhook URLs use `/webhooks/plex/{token}`. Existing `/api/webhook/plex/{token}` URLs remain
available for compatibility until Movary 1.0.

The webhook URL is connected to your Movary account. Keep it private. Regenerating or deleting it immediately invalidates the previous URL, which must then be removed or replaced in Plex.

The public `APPLICATION_URL` must be configured before Movary can generate webhook URLs. Contact the Movary administrator if the controls are disabled.

## Authenticate a Plex account

Authentication is required for the watchlist import but not for webhook scrobbling. An administrator must first configure the public `APPLICATION_URL` and `PLEX_IDENTIFIER`; see [Configuration](../configuration.md).

Open **Settings → Integrations → Plex**, select **Authenticate**, and complete the Plex sign-in flow. Movary stores the resulting access token for later Plex requests.

Removing authentication from Movary deletes its stored token but does not revoke that token in Plex. To revoke it there, open the Plex account settings, find **Authorized Devices**, and remove the Movary entry.

## Configure a Plex server URL

Authenticated features can use the Plex server URL saved on the integration page. Select **Test** to verify the address before saving it.

Movary allows HTTP or HTTPS destinations on ports 80, 443, 8096, 8920, and 32400. Public destinations are allowed by default. An administrator must explicitly authorize a private, reserved, or loopback destination with `PLEX_URL_ALLOWLIST`, for example:

```dotenv
PLEX_URL_ALLOWLIST=plex.home,192.168.1.20,fd00::/64
```

Hostname entries are exact matches; IP and CIDR entries authorize matching addresses. Prefer a single hostname or IP address over a broad network range.

## Import the Plex watchlist

After authenticating, select **Import watchlist** under **Settings → Integrations → Plex**. The import runs as a background job; select **Show imports** to review its status.

Movary adds missing Plex watchlist movies to the Movary watchlist without removing existing entries. Movies are matched through their TMDB identifiers.

Administrators with command-line access can run the same import directly:

```shell
php bin/console.php plex:watchlist:import --userId=USER_ID
```
