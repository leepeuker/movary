# Personal data

Open **Settings → Account → Personal data** to export, import, or permanently delete data belonging to your account.

## Export CSV files

Movary provides three downloads:

| Export | Columns |
|:-------|:--------|
| History | `title`, `year`, `tmdbId`, `imdbId`, `watchedAt`, `comment`, `location` |
| Ratings | `title`, `year`, `tmdbId`, `imdbId`, `userRating` |
| Watchlist | `title`, `year`, `tmdbId`, `imdbId`, `addedAt` |

Each export contains only data for your account. Store exported files securely if your watch activity is private.

## Import CSV files

The same page accepts history, ratings, and watchlist CSV files. Movary's own exports provide the expected column names and are the safest starting point for an import.

Imports process rows into the data already present in your account; they do not first clear or replace all existing data. In particular, importing history can add watch entries. Export your current data before a large import so you retain a separate copy.

Movies not already known to the installation are created from the identifiers and titles in the file. Movary then schedules TMDB metadata synchronization for them.

For imports from another service, use its integration page and guide when available, such as [Letterboxd](../features/letterboxd.md), [Netflix](../features/netflix.md), or [Trakt](../features/trakt.md).

## Delete history or ratings

The deletion actions are permanent:

- **Delete history** removes all watch entries from your account. It does not delete your ratings or watchlist.
- **Delete ratings** removes all movie ratings from your account. It does not delete your history or watchlist.

Movary asks for confirmation before either action. Export the corresponding CSV first if you may need the data again.

## Delete your account

**Delete account** permanently removes the account and its associated personal data, then signs you out. This cannot be undone through Movary.

Movie, person, and other shared metadata belong to the installation rather than to one user, so deleting your account does not remove that shared metadata.

An administrator's server backup covers the entire installation and is not a substitute for a personal export. Administrators can learn about server-wide recovery under [Backup and restore](../backup-and-restore.md).
