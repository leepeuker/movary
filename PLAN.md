# Frontend cache invalidation plan

## Goal

After a Movary deployment, the next normal page load must use compatible HTML, JavaScript, and CSS from the same release. Dynamic HTML and API data must not be reused without revalidation, while unchanged static assets may remain browser-cacheable.

## Current problem

- nginx marks JavaScript and CSS responses fresh for five days.
- With the exception of `app.js` in PR #856, templates reference assets using stable URLs. A browser can therefore combine new HTML with JavaScript or CSS from the previous release for up to five days.
- The legacy service worker cached `/` and `/index.php` with a cache-first strategy. Existing installations can continue receiving that cached HTML until an updated worker deletes the legacy cache.
- Dynamic HTML and JSON responses do not define an application-level cache policy. HTML currently benefits from PHP session defaults, but this is implicit and should not protect content containing user data or CSRF tokens.
- `fetch()` calls use the browser's default cache mode, so their behavior depends on response headers.

## Proposed design

### 1. Generate versioned URLs for every mutable frontend asset

Add a small asset URL generator and expose it to Twig as a function, for example:

```twig
<script src="{{ asset_url('js/movie.js') }}"></script>
<link rel="stylesheet" href="{{ asset_url('css/movie.css') }}">
```

The generated URL should preserve `applicationUrl` and append a version derived from that file's contents:

```text
/js/movie.js?v=<short-sha256>
```

Use a content hash rather than `APPLICATION_VERSION` because nightly images currently all use the value `nightly`, development may report `unknown`, and a content hash also handles deployments that are not made from an official image. Cache hashes within the generator for the duration of a request.

The generator should:

- accept only relative files beneath `public/`;
- reject missing files and paths that escape `public/`;
- URL-encode the version value;
- leave external URLs unsupported so their cache behavior remains explicit;
- have focused unit tests for URL generation, subdirectory installations, changed content, missing files, and path traversal.

Convert all local `<script src>` and stylesheet references in `templates/`, including base, page-specific, and vendored files. This avoids having two asset-loading conventions and prevents an unversioned file from becoming mutable later.

Keep nginx's five-day static-asset lifetime. Once URLs are content-versioned, the long lifetime is useful and safe: changed contents produce a different URL, while unchanged contents can remain cached.

### 2. Make dynamic response caching explicit

Introduce response-header helpers for these policies:

- Dynamic HTML containing user-specific data or CSRF tokens: `Cache-Control: private, no-cache`.
- Authenticated or mutable JSON: `Cache-Control: private, no-cache`.
- Sensitive responses that must not be stored at all, if any are identified during implementation: `Cache-Control: private, no-store`.
- Deliberately cacheable generated responses, such as placeholder SVGs, retain their existing explicit policy.

Prefer `private, no-cache` over applying `no-store` to every page so browsers may retain normal back/forward-cache behavior while still revalidating HTTP responses.

Implement the policy centrally in the HTTP response layer rather than editing every controller independently. Explicit cache headers supplied by a response must override the default. Add unit tests covering the default and the override.

Audit frontend GET requests while adding the headers. Do not add random query parameters or `cache: 'no-store'` to each `fetch()` call; correct response headers should define the shared policy consistently for browsers, proxies, and direct API consumers.

### 3. Preserve the legacy service-worker recovery

A general fix still needs the migration behavior from PR #856. Asset versioning cannot by itself remove HTML already held by the old Cache Storage worker.

Carry the following behavior into the general-fix branch:

- install and activate the replacement worker immediately;
- delete the legacy `movary` cache;
- claim controlled and uncontrolled window clients;
- reload each open window once after cleanup;
- persist a recovery marker so later activations do not repeatedly reload tabs;
- version the service-worker registration URL so a newly rendered page explicitly registers the current worker.

Add `updateViaCache: 'none'` to the registration and give `/serviceWorker.js` an exact nginx `no-cache` rule. The latter changes build/nginx configuration and therefore requires explicit maintainer approval before implementation under `AGENTS.md`.

The replacement worker must not add a `fetch` handler or cache HTML/API responses.

After the cleanup worker has shipped for an agreed transition period, handle retirement in a separate change: delete legacy caches, call `registration.unregister()`, and reload controlled clients once. Do not unregister it in the initial general fix because clients running the old cache-first worker must first receive the cleanup code.

### 4. Keep browser storage separate from release caching

Retain intentional `localStorage` entries for UI preferences and the short-lived releases list. They do not contain application code or server-rendered HTML and should not be cleared during deployment.

Document any future `localStorage` schema changes with their own version/migration rather than coupling them to the static-asset version.

## Suggested implementation sequence

Keep the work reviewable as focused commits, and split it into separate PRs if review becomes difficult:

1. Add and test the asset URL generator and Twig function.
2. Convert all local JavaScript and CSS references to the helper and add a test that detects unversioned local asset references.
3. Add explicit default cache headers for dynamic responses, with override tests.
4. Integrate the one-time legacy service-worker cleanup from PR #856.
5. With maintainer approval, add the exact nginx rule for `serviceWorker.js`.
6. Verify the complete upgrade path in browsers before merging.

Avoid unrelated frontend refactoring, dependency updates, bundlers, or framework changes.

## Verification

### Automated checks

Run and report all required checks:

```bash
composer test-cs
composer test-phpstan
composer test-psalm
composer test-unit
```

Add automated coverage for:

- identical asset contents producing identical URLs;
- changed asset contents producing different URLs;
- every local JS/CSS reference rendered through the asset helper;
- default dynamic response cache headers and explicit overrides;
- service-worker cache deletion and one-time recovery behavior, to the extent practical without introducing new frontend tooling.

### Manual upgrade scenarios

Test with browser DevTools without using “Disable cache”:

1. Install the version immediately before the fix and visit `/` so the legacy service worker and cache exist.
2. Keep `/`, a movie page, and a settings page open.
3. Deploy the fixed version.
4. Confirm the legacy `movary` cache is deleted and each open tab reloads once only.
5. Confirm the document, page-specific JS, and CSS requests use the new versioned URLs.
6. Confirm a normal reload does not repeat the recovery reload.
7. Change one JS file and verify only its URL changes when using per-file hashes.
8. Confirm authenticated HTML and JSON responses have the intended `Cache-Control` headers.
9. Repeat with Movary hosted at a non-root `APPLICATION_URL`.
10. Check Chromium and Firefox; include an installed-PWA launch in Chromium.

## Acceptance criteria

- No local JavaScript or CSS file is referenced by an unversioned URL.
- Changing any referenced JS/CSS file changes its rendered URL.
- Dynamic HTML and authenticated/mutable JSON explicitly require revalidation.
- Existing legacy service-worker caches are removed during upgrade.
- Open clients reload at most once for the legacy recovery.
- The active worker does not intercept normal frontend requests.
- Root and subdirectory installations both work.
- All required project checks pass, with any unrelated failures documented.

## Relationship to PR #856

PR #856 can be closed in favor of the general fix only after its legacy cache deletion and one-time client recovery have been incorporated into the replacement branch. Closing it first and implementing only general asset versioning would leave existing users trapped behind the old cache-first service worker.

In the replacement PR description, state that it supersedes #856, identify which recovery behavior was retained, and distinguish the one-time migration from the permanent asset/version and response-header policies.
