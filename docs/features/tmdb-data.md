# TMDB data

Movary uses [TMDB](https://www.themoviedb.org/) as its primary source for movie and person metadata. Administrators need to configure a valid TMDB API key before Movary can retrieve this data.

Set the key under **Settings → Server → General** or with the `TMDB_API_KEY` environment variable. Environment variables take precedence over values saved through the web interface. See [Configuration](../configuration.md) for the complete settings reference.

Administrators can choose a global movie poster from the English posters available on TMDB. Open the movie's **More** menu, select **Change poster**, and choose a poster. The selection is kept during later TMDB metadata synchronization. Use **Restore default** in the same dialog to return to TMDB's current default poster.

## Synchronize movie and person metadata

Open **Settings → Server → Job Queue**, select **Create Job**, and choose either **TMDB movie metadata sync** or **TMDB person metadata sync**.

The optional job fields let you:

- skip records updated more recently than a specified number of hours;
- limit the maximum number of records processed;
- target comma-separated local movie or person IDs;
- process only records that have never been synchronized.

Leaving both the record limit and local IDs empty creates an unbounded job for every eligible record, which requires confirmation. A job with the `done` status completed, but individual records that TMDB could not update may still be reported as warnings in the application log.

### Schedule recurring synchronization

Open **Settings → Server → Job Schedules** to run the same synchronization automatically. Select a job type, enter a five-field cron expression evaluated in UTC, configure its filters, and create the schedule.

The background worker must be running for schedules to create jobs. Missed occurrences do not create catch-up jobs, and an occurrence is skipped when a job of the same synchronization type is already waiting or in progress. See [Job processing](../job-processing.md) for worker setup, scheduling behavior, and troubleshooting.

Avoid every-minute metadata schedules because they can create sustained TMDB API load.

### Command-line alternative

Administrators with command-line access can run:

```shell
php bin/console.php tmdb:movie:sync
php bin/console.php tmdb:person:sync
```

Both commands support:

- `--hours`: skip records updated more recently than this number of hours;
- `--threshold`: limit the maximum number of records processed;
- `--never-synced`: process only records that have never been synchronized.

Use `--movieIds` with the movie command or `--personIds` with the person command to target comma-separated local IDs.

For example, synchronize at most 50 movies that have not been updated during the last 48 hours:

```shell
php bin/console.php tmdb:movie:sync --hours 48 --threshold 50
```

## Image cache

Set `TMDB_ENABLE_IMAGE_CACHING=1` to store local copies of TMDB images instead of loading them from TMDB for every request. Persist the `storage` directory so cached images survive container recreation or application upgrades.

To cache selected images, open **Settings → Server → Job Queue**, create a **TMDB image cache refresh** job, and enter at least one local movie or person ID. A movie job also caches images for its cast and crew. Targeted image-cache jobs can also be configured under **Settings → Server → Job Schedules**.

Command-line access additionally supports refreshing the complete cache:

```shell
php bin/console.php tmdb:imageCache:refresh
```

Use `--type movies` or `--type persons` to restrict the refresh, and `--force` to overwrite existing cached images.

Two maintenance commands are also available:

```shell
php bin/console.php tmdb:imageCache:cleanup
php bin/console.php tmdb:imageCache:delete
```

`cleanup` removes cached images that are no longer referenced. `delete` removes the complete image cache.

## Personal TMDB ratings

Movary does not provide a native integration for importing a TMDB user's personal ratings. A third-party tool such as [TMDBToMovary](https://github.com/SirMartin/TMDBToMovary) can provide this separately from server metadata synchronization.
