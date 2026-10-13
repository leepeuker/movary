## Description

Update the [IMDb](https://www.imdb.com/) rating of all local movies. 
Movies without IMDb ratings or updated the longest time ago are prioritized.

!!! Info

    Movary uses the official [IMDb datasets](https://datasets.imdbws.com/) to retrieve ratings.
    The ratings dataset file is automatically downloaded and cached for 24 hours.
    IMDb is only updating the ratings dataset once a day, so reducing the cache duration would not lead to any benefits.

Administrators can run the synchronization once under **Settings > Server > Job Queue** or create a recurring UTC cron schedule under **Settings > Server > Job Schedules**. Because IMDb updates its ratings dataset once a day, scheduling more than one run per day provides no benefit.

## Command
```shell
php bin/console.php imdb:sync
```

### Interesting flags

- `--help`
  Detailed information about the command
- `--hours`
  Number of hours required to have elapsed since last sync
- `--threshold`
  Maximum number of movies to sync
- `--movieIds`
  Comma separated string of movie ids to force sync for
- `--never-synced`
  Use to sync only movies which were never synced before

### Example

Update ratings for the first 30 movies which were not updated in the last 24 hours ago
```shell
php bin/console.php imdb:sync` --hours 24 --threshold 30
```
