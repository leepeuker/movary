# Dashboard and statistics

Open the navigation menu and select **Dashboard** to see an overview of your movie activity. Dashboard values are calculated from your history, ratings, watchlist, and movie metadata.

## Overview statistics

The overview at the top of the dashboard always shows:

- total plays;
- unique movies watched;
- hours watched;
- average plays per day;
- average movie runtime;
- average personal rating.

These values update as your history and ratings change. See [Movie tracking](tracking-movies.md) for managing the underlying data.

API clients can get the same six values from `GET /api/v1/users/<username>/dashboard/overview`, subject to the account's privacy setting. See the [OpenAPI specification](../openapi.json) for the response format.

## Statistic rows

The dashboard can display rows for:

- latest plays and latest cinema plays;
- most-watched actors, actresses, and directors;
- most-watched genres, languages, release years, production companies, and production countries;
- the latest movies in your watchlist;
- top locations;
- personal rating distribution.

Select a row heading on the dashboard to expand or collapse it. Many row items link to the corresponding movie or person page, or to a filtered movie list.

Location-based rows use the locations assigned to your plays. See [Locations](locations.md) for cinema classification and location visibility.

## Customize the dashboard

Open **Settings → Account → Dashboard** to configure the statistic rows:

- Use the eye button to show or hide a row.
- Use the expand button to choose whether a row starts expanded or collapsed.
- Use the arrow buttons to change its position.
- Select **Update** to save your changes.

Select **Reset** to restore the default row visibility, expansion, and order.

API clients can replace the configuration with `PUT /api/v1/users/<username>/dashboard/rows` or restore the defaults with `DELETE` on the same path. The `PUT` body contains a `rows` array with every row key exactly once. Each row needs a unique zero-based `position` and boolean `visible` and `initiallyExpanded` fields. Fetch the current keys and settings with `GET` on the same path. Write requests require an API token belonging to that user. See the [OpenAPI specification](../openapi.json) for the request format.

The overview statistics at the top of the dashboard are not part of the configurable row list.

## Dashboard visibility

Dashboard customization controls what appears on your dashboard for you and other permitted viewers. Your account privacy level determines who can open the dashboard and other user pages. See [Privacy and sharing](privacy-and-sharing.md).
