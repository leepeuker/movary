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

## Jellyfin Authentication

Some features require access to protected personal Jellyfin data.
You can authenticate Movary against Jellyfin on the Jellyfin integration settings page (`/settings/integrations/jellyfin`).

During the authentication process a Jellyfin access token is generated and stored in the database.
This token will be used in all further Jellyfin API requests.
When an authentication is removed in Movary, the token will be deleted from Movary and the Jellyfin server.
