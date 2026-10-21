# Emby

The Emby integration automatically records completed movie plays in Movary through an Emby webhook. Emby webhooks require an active [Emby Premiere](https://emby.media/support/articles/Webhooks.html) license on the server.

## Configure play scrobbling

1. Open **Settings → Integrations → Emby** in Movary.
2. Select **Regenerate url** to create a personal webhook URL.
3. Enable **Scrobble completed watches** and select **Save**.
4. Add the generated URL as a webhook in the Emby server and configure it for playback-stop events for the matching user.

Movary records only movie playback-stop events that Emby reports as played to completion. The movie must have a TMDB identifier so Movary can match or create it.

Movary validates the JSON in Emby's `data` form field before processing and rejects malformed
payloads or JSON fields larger than 256 KiB.

The public `APPLICATION_URL` must be configured before Movary can generate webhook URLs. Contact the Movary administrator if the controls are disabled.

Keep the webhook URL private because it is connected to your account. Regenerating or deleting it immediately invalidates the previous URL, which must then be replaced or removed in Emby.
