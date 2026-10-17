# Job processing

Movary uses a database-backed queue for work that should happen outside a web request, such as importing data, caching images, synchronizing metadata, or sending email.

The background worker must run regularly to process waiting jobs and create jobs from recurring schedules.

## Run the worker

Run the worker with:

```shell
php bin/console.php jobs:process
```

One invocation processes at most one job. When processing finishes quickly or no job is waiting, the command remains active until `MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING` has elapsed; the default is 15 seconds. The standard Docker image uses Supervisor to run the worker continuously. A manual installation must configure Supervisor or invoke the command regularly with cron; see [Manual installation](install/manual.md#job-processing).

Run only one worker process. Multiple workers can select the same waiting job because selecting and claiming a job are separate database operations.

## Monitor the queue

Open **Settings → Server → Job Queue** to filter jobs by user, type, or status.

| Status | Meaning |
|:-------|:--------|
| `waiting` | The job is ready for the worker. |
| `in progress` | The worker has claimed the job. |
| `done` | The job completed successfully. |
| `failed` | Processing stopped because an error occurred. |

The page also allows an administrator to:

- create a TMDB movie or person metadata synchronization, TMDB image-cache refresh, or IMDb ratings synchronization;
- delete an individual job;
- remove all processed jobs, which includes `done` and `failed` jobs; or
- remove all jobs, including jobs that are `waiting` or `in progress`.

Failed jobs are not retried automatically. Review the application log before removing a failed job or repeating its operation.

## Manage recurring schedules

Open **Settings → Server → Job Schedules** to create, enable, disable, or delete recurring TMDB and IMDb synchronization schedules. Schedules use five-field cron expressions evaluated in UTC.

For example, `*/15 * * * *` runs every 15 minutes, and `0 3 * * 1` runs every Monday at 03:00 UTC. See [TMDB data](features/tmdb-data.md) and [IMDb ratings](features/imdb-rating.md) for the available jobs and filters.

The worker checks schedules whenever it runs. Missed occurrences do not create catch-up jobs. If the same synchronization type is already waiting or in progress, the occurrence is skipped and the schedule advances to its next run. Failed jobs do not disable future occurrences.

Cron expressions have one-minute resolution, so the worker must run at least once per minute for minute-level accuracy. Avoid every-minute metadata schedules because they can create sustained external API load.

## Troubleshoot jobs

- A job that remains `waiting` usually means the worker is not running. Check the worker process and application logs.
- A job left `in progress` can mean that the worker stopped during processing. Inspect the logs and confirm that repeating the operation is safe before deleting the job and scheduling it again.
- A `failed` job records that processing threw an error. Check the application logs for the underlying exception.
- A schedule that does not create jobs may be disabled, may not yet be due, or may have a job of the same type already waiting or in progress.

Developers working on queue behavior can refer to [Job processing internals](development/job-processing.md).
