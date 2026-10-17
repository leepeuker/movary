# Integrations

Integrations connect a Movary account to media servers, players, movie services, and social platforms. Open **Settings → Integrations** and select a service to configure it.

## Available integrations

| Integration | Data added to Movary | Data sent from Movary |
|:------------|:---------------------|:----------------------|
| [Plex](plex.md) | Completed plays and rating updates through a webhook; watchlist import | — |
| [Jellyfin](jellyfin.md) | Completed plays through a webhook; history import | History export and optional automatic export of later changes |
| [Emby](emby.md) | Completed plays through a webhook | — |
| [Kodi](kodi.md) | Completed plays through the Movary add-on | — |
| [Letterboxd](letterboxd.md) | History and ratings import | History and ratings export |
| [Trakt](trakt.md) | History and ratings import | — |
| [Netflix](netflix.md) | Viewing-history import | — |
| [Radarr](radarr.md) | — | Watchlist feed |
| [Mastodon](mastodon.md) | — | Optional posts for plays and watchlist additions |

Each guide explains its prerequisites and limitations. Some features also require an administrator to configure the public application URL, a client identifier, or access to a private media server.

## How integration methods differ

- **Scrobbling** uses a webhook or add-on to record a completed play automatically when it happens.
- **Importing** copies existing data from another service into Movary. Imports normally add missing data unless a guide explicitly documents different behavior.
- **Exporting** sends or packages Movary data for another service.
- **Synchronization** keeps supported changes flowing automatically after it is enabled.
- **Feeds** provide a URL that another application reads periodically.
- **Cross-posting** publishes selected Movary activity to a social account.

## Protect integration credentials

Treat webhook URLs, feed URLs, and access tokens as credentials:

- do not publish or share them;
- use HTTPS for an internet-accessible Movary installation;
- regenerate a webhook or feed URL if it is exposed, then replace the old URL in the connected service; and
- remove authentication in Movary when the connection is no longer needed, following the service guide for any additional revocation step.

Before a large import or synchronization, consider creating a [personal data export](../user-guide/personal-data.md) or asking the administrator to verify the installation's [backups](../backup-and-restore.md).
