# Radarr

The Radarr integration exposes the current Movary watchlist as an import-list feed. Radarr reads this feed periodically and can add its movies to the Radarr library. Movary does not connect to Radarr or control how Radarr monitors, searches for, or downloads those movies.

## Create the watchlist feed

1. Open **Settings → Integrations → Radarr** in Movary.
2. Select **Regenerate url** to create a feed URL.
3. In Radarr, open **Settings → Import Lists**. This section is named **Lists** in some versions.
4. Add a list and select **StevenLu Custom** under the advanced list providers.
5. Paste the Movary feed URL into Radarr's **URL** field.
6. Configure Radarr's quality profile, root folder, monitoring, search, and other list options, then test and save the list.

Radarr imports changes on its own list-update schedule, so a watchlist addition may not appear immediately. See the [Radarr import-list documentation](https://wiki.servarr.com/radarr/settings#import-lists) for its current polling and list options.

## Protect or revoke the feed URL

The feed URL grants access to the titles and identifiers in your Movary watchlist without a Movary login. Treat it as a credential and do not publish it.

New feed URLs use `/feeds/radarr/{token}`. Existing `/api/feed/radarr/{token}` URLs remain available
for compatibility until Movary 1.0.

- **Regenerate url** immediately invalidates the previous URL. Replace it in Radarr afterward.
- **Delete url** disables the feed until a new one is generated.

Movary can only generate the feed after an administrator configures the public `APPLICATION_URL`. Contact the administrator if the controls are disabled.
