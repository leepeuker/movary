# Preferences

Open **Settings → Account → General** to control how Movary displays information for your account and how some movie actions behave.

## General preferences

The page provides these account settings:

- **Username** changes the name used for your account and user-page URLs. See [Account and security](account-and-security.md#change-your-username) before changing a shared URL.
- **Date format** controls how dates appear throughout Movary.
- **Privacy** controls who can view your user pages. See [Privacy and sharing](privacy-and-sharing.md).
- **Movie titles** displays localized titles, original titles, or both.
- **Country** supplies the default country when checking where a movie is available to watch.
- **Enable automatic removals from watchlist** removes a movie from your watchlist when you record a play for it.
- **Display cast character names on movie pages** shows character names below cast members.
- **Display TMDB ratings on movie pages** and **Display IMDb ratings on movie pages** control whether those public ratings appear beside their source links. These settings do not affect your personal rating.

Select **Submit** to save the preferences to your account.

API clients can read general account preferences with `GET /api/v1/me/preferences`. `PATCH` on the same path updates supplied fields, including username and privacy. The request requires your personal API token. See the [OpenAPI specification](../openapi.json) for fields and formats.

## Light and dark themes

Open the main navigation menu and use **Dark Mode** to switch between the light and dark themes. The selection is stored in the current browser rather than in your Movary account, so another browser or device can use a different theme.
