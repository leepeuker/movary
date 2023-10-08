## Trakt.tv API Access

Movary needs a Trakt **username** and **Client ID** to access its api. 
You can configure them in Movary via the user settings page at `/settings/integrations/trakt` or via cli (user update command).

#### How to get a Client ID
Go to your trakt.tv <a href="https://trakt.tv/oauth/applications" target="_blank">application settings</a> and create an application for Movary.
Trakt requires a redirect uri which is not used by Movary, you can enter a placeholder like `http://movary`:
<img src="/assets/trakt-new-application-page.png" alt="Trakt application seeting page with client ID" width="70%"/>

You should be able to see your Client ID now on your applications page:
<img src="/assets/trakt-view-application-page.png" alt="Trakt application seeting page with client ID" width="70%"/>

Enter the Client ID and your username in Movary, verify the access and save your changes. 

## Import

### Description

You can import your watch history and ratings from Trakt (only from public profiles currently).

The import will only add data missing in Movary on default, it will not overwrite or remove existing data.

The import can be triggered via the user settings page at `/settings/integrations/trakt` or via cli.

!!! Info

    Importing hundreds or thousands of movies for the first time can take a few minutes.

### CLI Command

```shell
php bin/console.php trakt:import
```

#### Interesting flags

- `--userId`
  User to import data to
- `--ratings`
  Import Trakt ratings
- `--history`
  Import Trakt watch history (plays)
- `--overwrite`
  Use if you want to overwrite the local data with the data coming from Trakt
- `--ignore-cache`
  Use if you want to force import everything regardless if there was a change since the last import

#### Example
Import history and ratings for user with id 1 and overwrite locally existing data

```shell
php bin/console.php trakt:import --userId=1 --ratings --history --overwrite
``` 
