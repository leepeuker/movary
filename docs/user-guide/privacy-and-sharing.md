# Privacy and sharing

Your privacy level controls who can view the movie data published on your user pages and through read-only user API endpoints.

## Choose a privacy level

Open **Settings → Account → General**, select a value under **Privacy**, and select **Submit**.

| Setting | Who can view your user data |
|:--------|:----------------------------|
| Not visible | Only you while signed in to your account. |
| Visible to users | You and other signed-in Movary users. |
| Visible to everyone | Signed-in users and visitors who are not signed in. |

The Admin role does not override **Not visible**; another administrator cannot view a private user page merely because they are an administrator.

## Data covered by this setting

The privacy check applies to pages below `/users/<username>/`, including:

- Your [dashboard and statistics](dashboard-and-statistics.md).
- History and watchlist pages.
- Movie, actor, and director lists.
- Movie and person detail pages shown in your user context.
- Reviews written on movie pages. A private review remains visible to its author.

It also applies when the API reads a user's history, watchlist, or played movies. API operations that change data still require a personal token belonging to that user.

Privacy changes who can view the data; it does not delete or transfer ownership of anything. Use [Personal data](personal-data.md) when you want to export or permanently delete your information.

## Share a user page

User-page URLs contain your username, for example:

```text
https://movary.example/users/username/history
```

Recipients can open the URL only when your privacy level permits it. Changing your username changes these URLs, so previously shared links using the old username stop working.
