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
- write or update the movie review;
- select a [location](locations.md), when locations are enabled;
- choose whether to post the activity to Mastodon, when that integration is enabled.

**Advanced mode** lets you add a comment that belongs only to this play.

The new play appears in **History**. Adding the same movie again with the same watch date creates another play and increases the play count for that date.

You can also open an existing movie page and select **Add play** to record another viewing without searching again.

API clients can list movies stored on the server with `GET /api/v1/movies?page=1&limit=24` and fetch shared details with `GET /api/v1/movies/<movaryId>`.
They can also list people with `GET /api/v1/persons`, filtering by `search`, `gender`, or `role=actor|director`, and fetch shared details with `GET /api/v1/persons/<personId>`.
Administrators can list available TMDB profile images with `GET /api/v1/persons/<personId>/profiles`, select one with `PUT /api/v1/persons/<personId>/profile` and a `profilePath` JSON field, or restore the default with `DELETE /api/v1/persons/<personId>/profile`.

## Track plays automatically

Movary can automatically add plays from supported media servers and players through webhooks, also called scrobbling:

- [Plex](../features/plex.md) can add movie plays and, optionally, ratings.
- [Jellyfin](../features/jellyfin.md), [Emby](../features/emby.md), and [Kodi](../features/kodi.md) can add movie plays.

Open **Settings → Integrations**, select the service, and follow its guide to generate and configure a personal webhook URL. Keep this URL private because it is connected to your account.

Scrobbled plays appear in **History** alongside plays added manually. Some integrations also provide separate import, export, or synchronization features; their guides explain the available options and any server-side requirements.

## View and edit history

Open the navigation menu and select **History**. You can search the history and switch between poster and table views. Select a movie to open its detail page.

On the movie page, select **View all** under the play count, then select a play to edit it. You can change its watch date or location. **Advanced mode** also lets you change the comment for that play and its ordering position among plays on the same date.

Select **Delete** in the edit dialog to permanently remove that play. This does not remove the movie's personal rating or add it back to the watchlist.

## Rate a movie

Ratings use a 1-to-10 scale and belong to the movie rather than to an individual play. You can assign a rating while adding a play.

To change a rating later, open the movie page, select the edit icon beside the rating stars, choose the new rating, and select **Update**. The updated rating is used throughout your movie lists and dashboard statistics.

Personal ratings are distinct from the public TMDB and IMDb ratings that may also appear on movie pages.

## Write reviews

A review is your current written opinion of a movie. It is separate from comments, history, and ratings, so you can review a movie without recording a play or assigning a rating.

Open the movie in your own user context and select **Add review**. Reviews are plain text and can contain up to 10,000 characters. Select **Contains spoilers** when appropriate.

When a review exists, anyone permitted by the review owner's [privacy setting](privacy-and-sharing.md) can select **Review** on the movie page to read it. Reviews marked as spoilers remain hidden until the viewer chooses to reveal them. When viewing your own review, select **Edit** to change or delete it.

## Manage the watchlist

To save a search result without recording a play, select **Add to watchlist** in the **Add movie** dialog. From an existing movie page, select **Add to Watchlist** or **Remove from Watchlist**.

Open the navigation menu and select **Watchlist** to search, filter, and sort saved movies or switch between poster and table views.

Movary can automatically remove a movie from your watchlist when you record a play. Enable or disable this behavior under **Settings → Account → General** with **Enable automatic removals from watchlist**.

## Import, export, or delete data in bulk

For CSV import and export, deleting all history, ratings, or reviews, or deleting your account, see [Personal data](personal-data.md). Imports from services such as Letterboxd, Trakt, and Netflix are documented under **Integrations**.
