## Webhook (Scrobbler)

### Description

Automatically add new [Jellyin](https://jellyfin.org/) movie plays to Movary.

!!! Info

    Requires the [webhook plugin](https://github.com/jellyfin/jellyfin-plugin-webhook) to be installed and active in Jellyfin.

### Instruction

- Generate a webhook url in Movary for your user on the Jellyfin integration settings page (`/settings/jellyfin/plex`)
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

    Requires the server configuration JELLYFIN_DEVICE_ID to be set.

During the authentication process a Jellyfin access token is generated and stored in Movary.
This token will be used in all further Jellyfin API requests.
When an authentication is removed from Movary, the token will be deleted in Movary and the Jellyfin server.

## Sync

### Automatic Sync

#### Description

You can keep your Jellyfin libraries automatically up to date with your latest Movary watch history changes.

!!! Info

    Requires active Jellyfin authencation


If the automatic sync is enabled, new plays added to Movary are automatically pushed to Jellyfin and the movies are marked as watched.
If a movie has its last play removed, Movary will set the movie to unwatched in Jellyfin.

Notes:

- can be enabled on the Jellyfin integration settings page
- movies will be updated in all Jellyfin libraries they exist
- only movies with a tmdb id in Jellyfin are supported and handled

#### CLI commands

- `php bin/console.php jellyfin:cache:refresh <userId>`
- `php bin/console.php jellyfin:cache:delete <userId>`
