# Movie tracking

Movary keeps four related kinds of personal movie data:

- **History** records when you watched a movie. A movie can have multiple watch dates and plays.
- **Ratings** store one personal score from 1 to 10 for each movie.
- **Reviews** store one written opinion for each movie, with an optional spoiler warning.
- **Watchlist** stores movies you plan to watch.

These are managed separately. For example, you can rate a movie without adding another play, or keep an unwatched movie on your watchlist.

## Add a play

Select **Add movie** in the navigation bar, search by title or enter a TMDB movie URL, and select the correct result.

Before selecting **Add play**, you can:

- change the watch date;
- assign a rating from 1 to 10;
- add a viewing note;
- select a [location](locations.md), when locations are enabled;
- choose whether to post the activity to Mastodon, when that integration is enabled.

The new play appears in **History**. Adding the same movie again with the same watch date increases the play count for that date instead of creating a separate entry.

You can also open an existing movie page and select **Add play** to record another viewing without searching again.

## Track plays automatically

Movary can automatically add plays from supported media servers and players through webhooks, also called scrobbling:

- [Plex](../features/plex.md) can add movie plays and, optionally, ratings.
- [Jellyfin](../features/jellyfin.md), [Emby](../features/emby.md), and [Kodi](../features/kodi.md) can add movie plays.

Open **Settings → Integrations**, select the service, and follow its guide to generate and configure a personal webhook URL. Keep this URL private because it is connected to your account.

Scrobbled plays appear in **History** alongside plays added manually. Some integrations also provide separate import, export, or synchronization features; their guides explain the available options and any server-side requirements.

## View and edit history

Open the navigation menu and select **History**. You can search the history and switch between poster and table views. Select a movie to open its detail page.

On the movie page, select **View all** under the play count, then select a play to edit it. You can change its watch date, viewing note, or location. **Advanced mode** also lets you change the number of plays recorded for that date and their ordering position.

Select **Delete** in the edit dialog to permanently remove that history entry. This does not remove the movie's personal rating or add it back to the watchlist.

## Rate a movie

Ratings use a 1-to-10 scale and belong to the movie rather than to an individual play. You can assign a rating while adding a play.

To change a rating later, open the movie page, select the edit icon beside the rating stars, choose the new rating, and select **Update**. The updated rating is used throughout your movie lists and dashboard statistics.

Personal ratings are distinct from the public TMDB and IMDb ratings that may also appear on movie pages.

## Write reviews

A review is your current written opinion of a movie. It is separate from viewing notes, history, and ratings, so you can review a movie without recording a play or assigning a rating.

Open the movie in your own user context and select **Add review**. Reviews are plain text and can contain up to 10,000 characters. Select **Contains spoilers** when appropriate. You can edit or delete your review from the same movie page.

## Manage the watchlist

To save a search result without recording a play, select **Add to watchlist** in the **Add movie** dialog. From an existing movie page, select **Add to Watchlist** or **Remove from Watchlist**.

Open the navigation menu and select **Watchlist** to search, filter, and sort saved movies or switch between poster and table views.

Movary can automatically remove a movie from your watchlist when you record a play. Enable or disable this behavior under **Settings → Account → General** with **Enable automatic removals from watchlist**.

## Import, export, or delete data in bulk

For CSV import and export, deleting all history, ratings, or reviews, or deleting your account, see [Personal data](personal-data.md). Imports from services such as Letterboxd, Trakt, and Netflix are documented under **Integrations**.
