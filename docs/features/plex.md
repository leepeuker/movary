## Webhook (Scrobbler)

### Description

Automatically add new [Plex](https://www.plex.tv/) movie plays and ratings to Movary.

!!! Info

    To use the required webhooks feature in Plex an active [Plex Pass](https://www.plex.tv/plex-pass/) subscription is neceessary.

### Instruction
- Generate a webhook url in Movary for your user on the Plex integration settings page (`/settings/integrations/plex`)
- Add the generated url as a [webhook to your Plex server](https://support.plex.tv/articles/115002267687-webhooks/) to start scrobbling

You can select what you want to scrobble (movie plays and/or ratings) via the "Scrobble Options" checkboxes on the settings page.

!!! tip

    Keep your webhook url private to prevent abuse.

## Plex Authentication

Some features require access to protected personal Plex data.
You can authenticate Movary against Plex on the Plex integration settings page (`/settings/integrations/plex`).

During the authentication process a Plex access token is generated and stored in the database. 
This token will be used in all further Plex API requests.
When an authentication is removed in Movary, the token will be deleted from the database.

!!! Info

    Removing the authentication only deletes the token stored in Movary itself. The token still exists in Plex.
    To invalidate the access token in Plex, go to your Plex settings at: Account -> Authorized devices -> Click on the red cross for the entry "Movary"

## Watchlist import

### Description

Import missing movies from your Plex Watchlist to your Movary Watchlist.
Missing movies imported to the Movary Watchlist are put at the beginning of the list in the same order as they are in Plex.

!!! Info

    Plex authentication is required. 

### Instruction

#### Web UI
You can schedule import jobs and see the status/history of past jobs on the Plex integration settings page (`/settings/integrations/plex`).

#### Command
You can directly trigger an import via CLI

```shell
php bin/console.php plex:watchlist:import --userId=<id>
```

!!! tip

    You could create a cronjob to regularly import your watchlist to keep up to date automatically. 
