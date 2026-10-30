# Personal data

Open **Settings → Account → Personal data** to export, import, or permanently delete data belonging to your account.

## Export CSV files

Movary provides four downloads:

| Export | Columns |
|:-------|:--------|
| History | `title`, `year`, `tmdbId`, `imdbId`, `watchedAt`, `comment`, `location` |
| Ratings | `title`, `year`, `tmdbId`, `imdbId`, `userRating` |
| Reviews | `title`, `year`, `tmdbId`, `imdbId`, `review`, `containsSpoilers`, `createdAt`, `updatedAt` |
| Watchlist | `title`, `year`, `tmdbId`, `imdbId`, `addedAt` |

The history export has one row per play, including multiple plays of the same movie on the same date. Each row includes that play's comment and location.

Each export contains only data for your account. Store exported files securely if your watch activity is private.

## Import CSV files

The same page accepts history, ratings, reviews, and watchlist CSV files. Movary's own exports provide the expected column names for an import.

Imports process rows into the data already present in your account; they do not first clear or replace all existing data. Importing history adds one play per CSV row. Export your current data before a large import so you retain a separate copy.

History import currently applies comments and locations by movie and watch date. If the CSV contains multiple plays of the same movie on the same date, their individual comments and locations may not be restored to the correct plays.

Movies not already known to the installation are created from the identifiers and titles in the file. Movary then schedules TMDB metadata synchronization for them.

For imports from another service, use its integration page and guide when available, such as [Letterboxd](../features/letterboxd.md), [Netflix](../features/netflix.md), or [Trakt](../features/trakt.md).

## Delete history, ratings, or reviews

The deletion actions are permanent:

- **Delete history** removes all watch entries from your account. It does not delete your ratings, reviews, or watchlist.
- **Delete ratings** removes all movie ratings from your account. It does not delete your history, reviews, or watchlist.
- **Delete reviews** removes all movie reviews from your account. It does not delete your history, ratings, or watchlist.

Movary asks for confirmation before either action. Export the corresponding CSV first if you may need the data again.

## Delete your account

**Delete account** permanently removes the account and its associated personal data, then signs you out. This cannot be undone through Movary.

Movie, person, and other shared metadata belong to the installation rather than to one user, so deleting your account does not remove that shared metadata.

An administrator's server backup covers the entire installation and is not a substitute for a personal export. Administrators can learn about server-wide recovery under [Backup and restore](../backup-and-restore.md).
