# IMDb ratings

Movary can update the public IMDb rating stored for every local movie. This is server-wide metadata and is separate from each user's personal movie rating.

Movary downloads the official [IMDb datasets](https://datasets.imdbws.com/) and caches the ratings dataset for 24 hours. IMDb updates the dataset once per day, so running this synchronization more than once per day normally provides no benefit.

## Run a ratings synchronization

Open **Settings → Server → Job Queue**, select **Create Job**, and choose **IMDb ratings sync**.

The optional job fields let you:

- skip ratings synchronized more recently than a specified number of hours;
- limit the maximum number of movies processed;
- target comma-separated local movie IDs;
- process only movies whose IMDb rating has never been synchronized.

Without local IDs, Movary prioritizes movies whose ratings have never been synchronized or were synchronized least recently. Leaving both the record limit and local IDs empty creates an unbounded job for every eligible movie, which requires confirmation.

## Schedule recurring synchronization

Open **Settings → Server → Job Schedules**, create an **IMDb ratings sync** schedule, and enter a five-field cron expression evaluated in UTC. A daily schedule is sufficient because the source dataset updates once per day.

The background worker must be running for schedules to create jobs. See [Job processing](../development/job-processing.md) for worker setup, scheduling behavior, job statuses, and troubleshooting.

## Command-line alternative

Administrators with command-line access can run:

```shell
php bin/console.php imdb:sync
```

The command supports:

- `--hours`: skip ratings synchronized more recently than this number of hours;
- `--threshold`: limit the maximum number of movies processed;
- `--movieIds`: target comma-separated local movie IDs;
- `--never-synced`: process only movies whose IMDb rating has never been synchronized.

For example, synchronize at most 30 ratings that have not been updated during the last 24 hours:

```shell
php bin/console.php imdb:sync --hours 24 --threshold 30
```
