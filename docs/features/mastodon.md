## Cross-posting

### Description

Movary can post each movie play or watchlist addition to a Mastodon account via the [`create` Mastodon API](https://docs.joinmastodon.org/methods/statuses/#create).

![screenshot of Mastodon post showing a film having been watched](../assets/mastodon-post-play-example.png)

### Instruction

!!! warning
    Do not share the API key with anyone or they will be able to post statuses to your Mastodon account

1. follow the instructions on the Mastodon Integration settings page on Movary (`/settings/integrations/mastodon`)
2. optionally post to Mastodon whenever you add a play or add to watchlist
    ![screenshot of Movary "log play" modal showing a setting to post to Mastodon](../assets/mastodon-logplaymodal-example.png)

## Test Commands

```bash
# post play
make app_mastodon_post_play
# or
php bin/console.php mastodon:post:play --userId=1 --movieId=1 --watchDate=today

# post watchlist
make app_mastodon_post_watchlist
# or
php bin/console.php mastodon:post:watchlist --userId=1 --movieId=1
```
