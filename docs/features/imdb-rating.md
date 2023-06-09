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

### Important flags

- `--help`
  Detailed information about the command
- `--hours`
  Only sync movie ratings which were last synced at least X hours ago
- `--threshold`
  Maximum number of movie ratings to sync

### Example

Update ratings for the first 30 movies which were updated at least 24 hours ago
```shell
php bin/console.php imdb:sync` --hours 24 --threshold 30
```
