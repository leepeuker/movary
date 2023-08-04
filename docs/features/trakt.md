## Import

### Description

You can import your watch history and ratings from Trakt.

The Trakt account used in the import process must have a Trakt username and client id set (can be set via settings page `/settings/trakt` or via cli).

The import can be triggered via the settings page or via cli.

!!! Info

    Importing hundreds or thousands of movies for the first time can take a few minutes. 

### Command

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
