You can import your watch history and ratings from trakt.tv (exporting from movary to trakt not supported yet).

The trakt account used in the import process must have a trakt username and client id set (can be set via settings page `/settings/trakt` or via cli `user:update`).

The import can be executed via the settings page `/settings/trakt` or via cli.

Example cli import (import history and ratings for user with id 1 and overwrite locally existing data if needed):

`php bin/console.php trakt:import --userId=1 --ratings --history --overwrite`

**Info:** Importing hundreds or thousands of movies for the first time can take a few minutes.

**Interesting flags:**

- `--userId`
  User to import data to
- `--ratings`
  Import trakt ratings
- `--history`
  Import trakt watch history (plays)
- `--overwrite`
  Use if you want to overwrite the local data with the data coming from trakt
- `--ignore-cache`
  Use if you want to force import everything regardless if there was a change since the last import
