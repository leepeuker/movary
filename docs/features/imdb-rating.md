## Description

Update the [IMDb](https://www.imdb.com/) rating of all local movies. 
Movies without IMDb ratings or updated the longest time ago are prioritized.

!!! Info

    Movary scrapes the IMDb website for the ratings.
    Changes to the IMDb website structure can break the scraping and have to be fixed.
    This happens from time to time.
    Please [report problems](https://github.com/leepeuker/movary/issues) so that this can be quickly handled.

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

### Example

Update ratings for the first 30 movies which were not updated in the last 24 hours ago
```shell
php bin/console.php imdb:sync` --hours 24 --threshold 30
```
