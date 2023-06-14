## Introduction

Automatically add new [Jellyin](https://jellyfin.org/) movie plays to Movary.

!!! Warning

    Requires the [webhook plugin](https://github.com/jellyfin/jellyfin-plugin-webhook) to be installed and active in Jellyfin.

## How-to
- Generate a webhook url in Movary for your user on the Jellyfin integration settings page (`/settings/jellyfin/plex`)
- Add the generated url in the Jellyfin webhook plugin as a `Generic Destination` and activate only:
    - Notification Type => "Playback Stop"
    - User Filter => Choose your user
    - Item Type => "Movies" + "Send All Properties (ignores template)"

!!! tip

    Keep your webhook url private to prevent abuse.
