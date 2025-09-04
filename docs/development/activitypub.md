## ActivityPub

(not yet complete) ActivityPub implementation, following <https://github.com/leepeuker/movary/issues/686>

### Requests

This is broadly "how the requests *should* look", and can be used for a very bad way of testing that all is well with the ActivityPub endpoints.

#### `/.well-known/host-meta`

```bash
$ curl "http://movary.test/.well-known/host-meta"
<?xml version="1.0" encoding="UTF-8"?>
<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
    <Link rel="lrdd" template="http://movary.test/.well-known/webfinger?resource={uri}" />
</XRD>
```

#### `/.well-known/webfinger…`

```bash
$ curl "http://movary.test/.well-known/webfinger?resource=acct:alifeee@movary.test" | jq
{
  "subject": "acct:acct:alifeee@movary.test@alifeee",
  "links": [
    {
      "rel": "self",
      "type": "application/activity+json",
      "href": "movary.test/activitypub/users/alifeee"
    }
  ]
}
```

#### `/.well-known/nodeinfo`

```bash
$ curl "http://movary.test/.well-known/nodeinfo" | jq
{
  "links": [
    {
      "rel": "self",
      "type": "http://nodeinfo.diaspora.software/ns/schema/2.1",
      "href": "movary.test/nodeinfo/2.1"
    }
  ]
}
```

#### `/nodeinfo/2.1`

```bash
$ curl "http://movary.test/nodeinfo/2.1" | jq
{
  "version": "2.1",
  "software": {
    "name": "movary",
    "version": "unknown",
    "repository": "https://github.com/leepeuker/movary/"
  },
  "protocols": [
    "activitypub"
  ],
  "services": {
    "inbound": [],
    "outbound": []
  },
  "openRegistrations": false,
  "usage": {
    "users": {
      "total": 1
    }
  },
  "metadata": {
    "nodeName": "Local Movary"
  }
}
```
