# Webhook validation design proposal

!!! note

    This document proposes a contract for validating incoming webhook requests. It does not
    describe implemented behavior yet. Implementation requires maintainer approval because these
    integrations can add watch-history and rating data.

Movary accepts incoming webhooks from Plex, Jellyfin, Emby, and Kodi at personal URLs containing
a secret identifier. The controllers currently decode and process each provider differently.
Malformed requests can therefore be ignored, produce PHP notices, or reach the generic API error
handler, and the complete incoming payload is written to debug logs.

## Current transports

| Provider | Current request format | Accepted events |
| --- | --- | --- |
| Plex | `multipart/form-data`; JSON in the `payload` form field | Movie `media.scrobble` and `media.rate` |
| Jellyfin | JSON request body | Completed movie `PlaybackStop` |
| Emby | JSON in the `data` form field | Completed movie `playback.stop` |
| Kodi | JSON request body | Movie play reported by the Movary Kodi add-on |

Plex may include a thumbnail as a separate multipart field. Validation must therefore limit the
JSON `payload` field rather than reject an otherwise valid request solely because its complete
multipart body contains an attachment.

The Plex transport is described in the [Plex webhook documentation][plex-webhooks], and Jellyfin
documents the [Generic Destination][jellyfin-notifications] used by Movary. The Kodi format is
controlled by the [Movary Kodi add-on][kodi-addon]. Before implementing Emby content-type
enforcement, its form encoding must be confirmed against a captured request from the currently
supported Emby version; the public Emby documentation does not define that detail clearly enough
to tighten it safely.

## Proposed response contract

Validate the secret identifier before parsing or logging the request body.

| Condition | Response | Reason |
| --- | --- | --- |
| Unknown or expired webhook identifier | `404 Not Found` | Preserves the existing behavior without revealing whether an account exists. |
| Missing payload, malformed JSON, wrong top-level type, or invalid required fields | `400 Bad Request` | Makes integration configuration and payload errors visible. |
| Syntactically valid event that Movary does not support | `200 OK` | The delivery was understood; retrying it cannot make it relevant. |
| Valid event accepted or deliberately ignored by user settings | `200 OK` | Preserves normal provider delivery behavior. |
| Unexpected processing failure | `500 Internal Server Error` | Allows a transient failure to be retried where the sender supports retries. |

Webhook errors should use the existing flat API JSON error representation. No validation error
may include the secret URL, the raw payload, or an internal exception message.

This proposal deliberately distinguishes malformed input from a valid but irrelevant event. The
alternative is to return `200 OK` for malformed input to suppress retries. That hides broken
integration configuration and makes diagnosis difficult, so `400 Bad Request` is recommended.
Provider retry behavior should be checked during implementation and documented where it changes
the operational trade-off.

## Validation boundaries

The controller should validate the transport envelope before calling a scrobbler:

- Require the provider-specific payload location shown above.
- Require a JSON object, not a scalar or list.
- Reject a JSON field larger than 256 KiB with `413 Payload Too Large`. This limit is a proposed
  starting point and requires approval or adjustment using representative payloads.
- Enforce the provider's content type only after its actual sender behavior is verified. Accept
  content-type parameters such as a charset.

The provider mapper should then classify the decoded event before any movie lookup, TMDB sync, or
history/rating write:

- `unsupported`: the event is well formed but not handled by Movary;
- `invalid`: the event claims to be supported but lacks a required field or contains an invalid
  value;
- `accepted`: every field needed for processing has been validated and converted to its expected
  type.

This classification avoids treating a missing discriminator as merely unsupported and prevents a
partially validated event from triggering external requests or database writes. Existing valid
events and the current decision to ignore movies without a TMDB identifier should remain
compatible unless separately approved.

## Logging

Remove raw webhook payloads from controller and scrobbler log messages. Log only structured,
non-secret context needed to diagnose processing, such as provider, event type, outcome, Movary
movie ID, and watch date. Never log the webhook identifier or full provider account, server, or
media metadata.

## Implementation slices after approval

Keep the implementation reviewable and provider-compatible:

1. Add shared webhook envelope/error handling and tests without changing accepted provider
   payloads.
2. Migrate Plex and document its multipart request body in OpenAPI.
3. Migrate Jellyfin and Kodi raw-JSON bodies.
4. Confirm Emby's actual request encoding, then migrate Emby.
5. Update each OpenAPI operation alongside its implementation with its request body and `400`,
   `413`, and `500` responses.

Each slice should cover unknown identifiers, missing and malformed payloads, non-object JSON,
unsupported events, valid delegation, payload-size boundaries, and the absence of raw payloads in
logs. Request-level tests remain SQLite-only; controller and mapper tests should isolate TMDB and
database writes.

## Approval requested

Before implementation, maintainers should confirm:

1. `400` for malformed requests and `200` for valid unsupported events.
2. A 256 KiB limit on each JSON payload field/body, excluding Plex attachment size.
3. Provider-by-provider delivery, beginning with shared handling and Plex.
4. Deferring strict Emby content-type enforcement until its current request encoding is verified.

[jellyfin-notifications]: https://jellyfin.org/docs/general/server/notifications/
[kodi-addon]: https://github.com/leepeuker/movary-kodi-addon
[plex-webhooks]: https://support.plex.tv/articles/115002267687-webhooks/
