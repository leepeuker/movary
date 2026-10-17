# Movary documentation

  <p align="center">
    <img src="images/logo.png" alt="Movary logo" width="160">
  </p>

Movary is a self-hosted web application for tracking, rating, and exploring movie watch history. These guides cover installation, account features, integrations, and operating a Movary server.

## Start here

| I want to… | Read… |
|:-------------|:------|
| Install Movary with the recommended method | [Docker installation](install/docker.md) |
| Install Movary without Docker | [Manual installation](install/manual.md) |
| Finish setting up a new installation | [Getting started](getting-started.md) |
| Track movies and manage my account | [User guide](user-guide/index.md) |
| Connect Plex, Jellyfin, Letterboxd, or another service | [Integrations](features/integrations.md) |
| Configure and maintain a server | [Administration](administration.md) |
| Work on Movary itself | [Development setup](development/setup.md) |

## Use Movary

The [User guide](user-guide/index.md) explains the features available to every account:

- [track watch history, ratings, and a watchlist](user-guide/tracking-movies.md);
- [browse movies, actors, directors, and other users' public pages](user-guide/browsing-movies-and-people.md);
- [customize the dashboard and statistics](user-guide/dashboard-and-statistics.md);
- manage [preferences](user-guide/preferences.md), [account security](user-guide/account-and-security.md), [privacy](user-guide/privacy-and-sharing.md), and [personal data](user-guide/personal-data.md); and
- [connect media servers, players, and movie services](features/integrations.md).

## Run a Movary server

The [Administration guide](administration.md) is for server administrators. It covers configuration, users, email, metadata updates, background jobs, and backups.

New administrators should follow [Getting started](getting-started.md) after installation. For every available environment variable and its web-interface equivalent, see [Configuration](configuration.md).

## Develop and contribute

Start with the [development setup](development/setup.md), then use the [directory structure](development/file-structure.md) and [backend overview](development/structure.md) to find your way around the codebase.

Contribution guidelines, the issue tracker, and the source code are available in the [Movary GitHub repository](https://github.com/leepeuker/movary).

## Get help

- Check the [FAQ](faq.md) for common questions.
- Ask usage and development questions in [GitHub Discussions](https://github.com/leepeuker/movary/discussions).
- Report reproducible bugs or documentation problems through [GitHub Issues](https://github.com/leepeuker/movary/issues/new/choose).
- Join the community on [Discord](https://discord.gg/KbcSqggrgW).
