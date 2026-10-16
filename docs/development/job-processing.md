# Job processing

Movary uses a database-backed queue for work that should happen outside an HTTP request, such as importing data from third-party services, caching images, or sending an email.

## How a job moves through the queue

Code schedules a job through `JobQueueApi`. The job is stored in the `job_queue` table with:

- a type that identifies the work;
- a status;
- an optional user ID;
- optional JSON-encoded parameters;
- creation and update timestamps.

New background jobs normally start with the `waiting` status. Every invocation of the worker first materializes due recurring schedules as ordinary queue jobs, then:

1. selects the oldest `waiting` job;
2. changes its status to `in progress`;
3. passes it to `JobProcessor`, which selects the service for its type;
4. changes its status to `done` when processing succeeds, or `failed` when an exception is thrown.

The worker writes the exception to the application log and exits with a failure code when processing fails. Failed jobs are not retried automatically.

The four possible statuses are:

| Status | Meaning |
| --- | --- |
| `waiting` | The job is ready for the worker. |
| `in progress` | The worker has claimed the job. |
| `done` | The job completed successfully. |
| `failed` | Processing threw an exception. |

Some CLI commands also create job records directly with `in progress` to track their execution. The work itself is performed by that CLI command rather than by the queue worker.

## Running the worker

Run the worker with:

```shell
php bin/console.php jobs:process
```

One invocation processes at most one job. After successfully processing a job, or when there is no job to process, it waits before exiting when necessary so that its total runtime is at least `MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING`, which defaults to 15 seconds. Long-running jobs are not delayed further. The minimum runtime can be overridden for one invocation with `--minRuntime`.

The standard Docker image uses Supervisor to restart the command after it exits, creating a continuous worker. A manual installation must configure Supervisor or invoke the command regularly with cron; see [Manual installation](../install/manual.md#job-processing).

## Recurring schedules

Administrators can create recurring TMDB metadata, TMDB image-cache, and IMDb ratings synchronization schedules under **Settings > Server > Job Schedules**. Schedules use standard five-field cron expressions in UTC: minute, hour, day of month, month, and day of week. For example, `*/15 * * * *` runs every 15 minutes and `0 3 * * 1` runs every Monday at 03:00 UTC.

See [TMDB data](../features/tmdb-data.md) and [IMDb ratings](../features/imdb-rating.md) for the administrator workflows and available synchronization filters.

When a schedule is due, `jobs:process` attempts to add a normal `waiting` job and advances the schedule to its next future occurrence. Missed occurrences do not create catch-up jobs. If the same synchronization type is already waiting or in progress, that occurrence is skipped and the schedule still advances. Failed queue jobs remain in history and do not disable future occurrences.

Cron expressions have one-minute resolution. A schedule is checked only when `jobs:process` runs, so the worker must run at least once per minute for minute-level accuracy. Every-minute schedules can create sustained TMDB API load. The active-job check prevents overlapping work of the same type, but administrators should normally choose a longer interval.

!!! Warning

    Run only one worker process. Selecting a waiting job and changing its status are separate database operations, so concurrent workers can select the same job.

## Monitoring and cleanup

Administrators can inspect the queue under **Settings > Server > Job Queue**. The page supports filtering by user, type, and status. It also allows an administrator to:

- enqueue a TMDB movie or person metadata synchronization, a TMDB image-cache refresh, or an IMDb ratings synchronization;
- delete an individual job;
- remove all processed jobs (`done` and `failed`);
- remove all jobs, including `waiting` and `in progress` jobs.

Administrators can create, enable, disable, and delete recurring TMDB and IMDb synchronization schedules under **Settings > Server > Job Schedules**.

A job that remains `waiting` usually means the worker is not running. Check the worker process and application logs. A job left `in progress` can indicate that the worker stopped during processing. Because jobs are not retried automatically, inspect the logs and confirm that repeating the operation is safe before deleting the record and scheduling the work again.

## Adding a background job

The main queue components are:

- `src/ValueObject/JobType.php`: defines valid job types;
- `src/JobQueue/JobQueueApi.php`: provides methods that enqueue jobs;
- `src/JobQueue/JobQueueRepository.php`: persists and retrieves queue records;
- `src/JobQueue/JobScheduleRepository.php`: persists recurring schedules and their next run times;
- `src/JobQueue/JobScheduleProcessor.php`: materializes due schedules as queue jobs;
- `src/Command/ProcessJobs.php`: claims one waiting job and updates its status;
- `src/Service/JobProcessor.php`: dispatches a claimed job to its processing service.

To add a job, define its type, add a focused enqueue method to `JobQueueApi`, and add the corresponding dispatch branch and processing service to `JobProcessor`. Pass only the identifiers and small values needed to perform the work as job parameters. Add unit tests for scheduling, dispatch, successful processing, and failure behavior where applicable.

Keep in mind that a user can be deleted while their historical jobs remain. In that case the job's `user_id` becomes `NULL`, so processing code must not assume that an optional user association still exists.
