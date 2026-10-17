# Job processing internals

Movary uses a database-backed queue for work that should happen outside an HTTP request, such as importing data from third-party services, caching images, or sending an email.

See the administrator guide to [Job processing](../job-processing.md) for worker setup, recurring schedules, queue monitoring, and troubleshooting.

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

Some CLI commands also create job records directly with `in progress` to track their execution. The work itself is performed by that CLI command rather than by the queue worker.

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
