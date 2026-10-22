# Kodi

The Kodi integration automatically records completed movie plays in Movary through the official [Movary Kodi add-on](https://github.com/leepeuker/movary-kodi-addon).

## Configure play scrobbling

1. Open **Settings → Integrations → Kodi** in Movary.
2. Select **Regenerate url** to create a personal webhook URL.
3. Enable **Scrobble completed watches** and select **Save**.
4. Install the Movary add-on from a ZIP as described in its repository.
5. Open **Settings → My add-ons → Services → Movary → Configure** in Kodi.
6. Enter the generated webhook URL and enable the add-on.

The add-on reports a movie when playback finishes, or when playback is stopped after at least 90 percent of its runtime and at least 10 seconds. The Kodi movie must have a TMDB identifier so Movary can match or create it.

The add-on sends JSON with the `application/json` content type. Movary rejects malformed payloads
and JSON request bodies larger than 256 KiB.

New webhook URLs use `/webhooks/kodi/{token}`. Existing `/api/webhook/kodi/{token}` URLs remain
available for compatibility until Movary 1.0.

The public `APPLICATION_URL` must be configured before Movary can generate webhook URLs. Contact the Movary administrator if the controls are disabled.

Keep the webhook URL private because it is connected to your account. Regenerating or deleting it immediately invalidates the previous URL, which must then be replaced in Kodi.
