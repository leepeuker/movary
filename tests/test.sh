#!/bin/bash
curl --request POST \
  --url 'http://127.0.0.1/plex' \
  --form 'payload={
    "event": "media.scrobble",
    "user": false,
    "owner": true,
    "Account": {
      "id": 19084402,
      "thumb": "https:\/\/plex.tv\/users\/d6b812aa1374ec77\/avatar?c=1648308292",
      "title": "Anna Peuker"
    },
    "Server": {
      "title": "Pleex",
      "uuid": "27e03b118babf1f25f8e9930678545c623e18b67"
    },
    "Player": {
      "local": true,
      "publicAddress": "91.11.71.208",
      "title": "SHIELD Android TV",
      "uuid": "a899167a24aea9ab-com-plexapp-android"
    },
    "Metadata": {
    }
}'
