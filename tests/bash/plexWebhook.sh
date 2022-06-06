#!/bin/bash
curl --request POST \
  --url 'http://127.0.0.1/plex' \
  --form 'payload={
    "event": "media.scrobble",
    "user": true,
    "owner": true,
    "Account": {
        "id": 1,
        "thumb": "https://plex.tv/users/xxx/avatar?c=xxx",
        "title": "[your-plex-account]"
    },
    "Server": {
        "title": "TVTime Fake Server",
        "uuid": "xxx"
    },
    "Player": {
        "local": true,
        "publicAddress": "172.17.0.1",
        "title": "Firefox",
        "uuid": "xxx"
    },
    "Metadata": {
        "librarySectionType": "show",
        "ratingKey": "83782",
        "key": "/library/metadata/83782",
        "parentRatingKey": "83764",
        "grandparentRatingKey": "83763",
        "guid": "com.plexapp.agents.thetvdb://76156/1/18?lang=en",
        "librarySectionTitle": "Series",
        "librarySectionID": 2,
        "librarySectionKey": "/library/sections/2",
        "type": "episode",
        "title": "Episode 18",
        "grandparentKey": "/library/metadata/83763",
        "parentKey": "/library/metadata/83764",
        "grandparentTitle": "Scrubs",
        "parentTitle": "Season 1",
        "contentRating": "TV-PG",
        "summary": "",
        "index": 1,
        "parentIndex": 1,
        "viewCount": 1,
        "lastViewedAt": 1537996938,
        "thumb": "/library/metadata/83782/thumb/1535581275",
        "art": "/library/metadata/83763/art/1535581275",
        "parentThumb": "/library/metadata/83764/thumb/1535581275",
        "grandparentThumb": "/library/metadata/83763/thumb/1535581275",
        "grandparentArt": "/library/metadata/83763/art/1535581275",
        "addedAt": 1535581221,
        "updatedAt": 1535581275
    }
}'
