<h1 align="center">
  <a href="https://movary.org"><img src="https://github.com/leepeuker/movary/raw/main/public/images/movary-logo-192x192.png" height="160px" width="160px"></a>
  <br>
  Movary
  <br>
</h1>

<h4 align="center">The central hub to track, rate and explore your movie watch history</h4>

<p align="center">
<a href="https://github.com/leepeuker/movary/actions/workflows/tests.yml" target="_blank" rel="noopener noreferrer"><img src="https://github.com/leepeuker/movary/actions/workflows/tests.yml/badge.svg" alt="Test status"></a>
<a href="https://github.com/leepeuker/movary/releases/latest" target="_blank" rel="noopener noreferrer"><img src="https://img.shields.io/github/v/release/leepeuker/movary?display_name=tag" alt="Latest release"></a>
<a href="https://hub.docker.com/r/leepeuker/movary" target="_blank" rel="noopener noreferrer"><img src="https://img.shields.io/docker/pulls/leepeuker/movary" alt="Docker pulls"></a>
<a href="https://github.com/leepeuker/movary" target="_blank" rel="noopener noreferrer"><img src="https://img.shields.io/github/stars/leepeuker/movary?style=flat&color=yellow&label=github%20stars" alt="GitHub stars"></a>
<a href="https://github.com/leepeuker/movary/issues" target="_blank" rel="noopener noreferrer"><img src="https://img.shields.io/github/issues/leepeuker/movary?color=eba434&label=github%20issues" alt="Open GitHub issues"></a>
<a href="https://discord.gg/KbcSqggrgW" target="_blank" rel="noopener noreferrer"><img src="https://img.shields.io/discord/1125830398715363399" alt="Discord server"></a>
<a href="https://github.com/leepeuker/movary/blob/main/LICENSE" target="_blank" rel="noopener noreferrer"><img src="https://img.shields.io/github/license/leepeuker/movary" alt="License"></a>
</p>

<p align="center">
<a href="https://movary.org" target="_blank" rel="noopener noreferrer">Website</a> •
<a href="https://docs.movary.org/install/docker/" target="_blank" rel="noopener noreferrer">Installation</a> •
<a href="https://docs.movary.org/" target="_blank" rel="noopener noreferrer">Docs</a> •
<a href="https://api.movary.org/" target="_blank" rel="noopener noreferrer">API</a> •
<a href="https://demo.movary.org/" target="_blank" rel="noopener noreferrer">Demo</a>
</p>

Movary is a free and open source web application to track, rate and explore your movie watch history.
You can host it for yourself and others.
It offers detailed statistics, third-party integrations for importing and exporting your history from platforms like
Trakt, Letterboxd, and Netflix, automated play tracking for Plex, Jellyfin, Emby, and Kodi, cross-posting to Mastodon,
and much [more](#features).

![Movary Dashboard Example](https://raw.githubusercontent.com/leepeuker/movary/main/docs/images/dashboard-screenshot.png)


**Disclaimer:** This project is still in an experimental (but usable) state.
Breaking changes may be introduced before the 1.0 release. Back up your data and review the release notes before updating.

## Quick start

The recommended installation method is the official Docker image. Follow the
[Docker installation guide](https://docs.movary.org/install/docker/) to prepare the required configuration and start
Movary, then complete the [getting started guide](https://docs.movary.org/getting-started/).

For installations without Docker, see the [manual installation guide](https://docs.movary.org/install/manual/).

## One-click deploy

[![Deploy on RepoCloud](https://d16t0pc4846x52.cloudfront.net/deploylobe.svg)](https://repocloud.io/details/Movary/)

## Features

- Movie watch history: Collect and manage your watch history and ratings
- Statistics: Explore your most watched actors, directors, genres, languages, years, and more
- Customization: Choose how your dashboard looks, how dates are displayed, and more
- Third-party integrations: Import and export history and ratings with services such as Trakt, Letterboxd, and Netflix
- Fediverse: Cross-post activity to Mastodon
- Scrobbling: Automatically add plays and ratings from Plex, Jellyfin, Emby, or Kodi
- Data ownership: Control who can see your data, and export or delete your data and account at any time
- Local metadata: Store the movie metadata used by Movary locally, using sources such as TMDB and IMDb
- PWA: Install Movary as a smartphone app ([how to install PWAs in Chrome](https://support.google.com/chrome/answer/9658361?hl=en&co=GENIE.Platform%3DAndroid&oco=1))
- User management: Use Movary alone or with others
- Free and open source, without ads or tracking

## Demo

Try the [demo installation](https://demo.movary.org/) with email `testUser@movary.org` and password `testUser`.

## Documentation

The [Movary documentation](https://docs.movary.org) covers installation, configuration, features, integrations, and
administration. Please report anything that is missing or incorrect.

## Support

- Please report bugs and request features or changes via [GitHub issues](https://github.com/leepeuker/movary/issues/new/choose)
- Ask for help or discuss related topics via [GitHub discussions](https://github.com/leepeuker/movary/discussions)
- Join our [Discord server](https://discord.gg/KbcSqggrgW)

## Contributing

Contributions are welcome. Start with the [development setup](https://docs.movary.org/development/setup/) and keep
changes focused on one concern.

For substantial features or architectural changes, please open a
[GitHub discussion](https://github.com/leepeuker/movary/discussions) first so the approach can be agreed on before
implementation.

## Contributors

* [@leepeuker](https://github.com/leepeuker) as Lee Peuker
* [@JVT038](https://github.com/JVT038) as JVT038
* [@pbogre](https://github.com/pbogre) as Pietro Bonaldo Gregori
* [@alifeee](https://github.com/alifeee) as alifeee
* [@sahinakkaya](https://github.com/sahinakkaya) as Şahin Akkaya

