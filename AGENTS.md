# AGENTS.md

Guidance for AI coding agents (Cascade, Copilot, Claude Code, Codex, etc.) working on Movary.

Movary is a community-maintained open source project. Every change that ships is a change
a human maintainer has to understand, review, and support forever after. **The goal of this
file is to make AI contributions small, obvious, and reviewable — not to make agents fully
autonomous.**

## 0. Ground rules

- **A human must review and understand every change before it is merged.** Agents must not
  merge their own PRs, dismiss review comments, or bypass CI/branch protections.
- **Prefer small, single-purpose diffs** over large multi-file rewrites. If a task naturally
  splits into independent pieces, open separate PRs/commits instead of one giant change.
- **Never invent behavior.** If requirements, APIs, or existing conventions are unclear,
  say so explicitly in the PR description or ask instead of guessing.
- **Do not refactor unrelated code** while doing a fix or feature, even if it "looks wrong".
  Flag it separately instead.
- **No unexplained code.** Every non-trivial change must be accompanied by a short, plain
  language explanation of *why*, in the PR description or commit message, not just *what*.
- When in doubt about scope, do less. A minimal correct patch is preferred over a large
  speculative one.

## 1. Project overview

- Movary is a PHP web app to track/rate/explore movie watch history (see `README.md`).
- Backend: PHP 8.5, custom routing via `nikic/fast-route` (`settings/routes.php`), PHP-DI
  for dependency injection, Doctrine DBAL for persistence, Phinx for migrations.
- Frontend: server-rendered Twig templates (`/templates`) + vanilla JS/CSS (`/public`). No
  SPA framework — keep changes consistent with this style, don't introduce new frontend
  frameworks/build tooling without explicit maintainer approval. There is no bundler, npm,
  or `package.json`: JS files in `public/js` are served as-is (no ES `import`/`export`,
  no transpilation), and third-party libs (Bootstrap etc.) are vendored into `public/`.
- Directory layout is documented in `docs/development/file-structure.md`:
  - `src/Api` — external API clients (Trakt, TMDB, Plex, Jellyfin, Emby, etc.)
  - `src/Command` — CLI commands, run via `bin/console.php`
  - `src/HttpController` — HTTP controllers/middleware for the API and web UI
  - `src/Service` — routing, cross-cutting processing of external API data, misc core logic
  - `src/Domain` — core domain model (movies, users, watch dates, etc.)
  - `src/Util` — small shared utilities
  - `templates/` — Twig templates, mirrors page/component structure
  - `db/migrations` — Phinx migrations (mysql + sqlite)
- Local dev setup and Docker workflow are documented in `docs/development/setup.md`.

## 2. Setup & environment

Do not assume network/Docker access is available in your execution environment. If it is:

```bash
cp .env.example .env      # set USER_ID and TMDB_API_KEY
make build_development     # builds dev image, installs composer deps, migrates DB
```

If Docker isn't available, PHP dependencies can be installed directly with Composer
(`composer install`) provided a compatible PHP 8.5 environment exists locally.

By default the dev setup uses SQLite. To test against MySQL instead, use
`docker-compose.mysql.yml` (see `docs/development/setup.md`) — do this when a change touches
SQL that could behave differently across the two supported database backends.

## 3. Required checks before proposing a change

Run everything `composer test` runs (see `composer.json`) and report the results in the PR
description. Do not silently skip a failing check — surface it and explain why (e.g.
pre-existing failure unrelated to your change).

```bash
composer test-cs       # PHP_CodeSniffer, settings/phpcs.xml
composer test-phpstan  # PHPStan level 8, settings/phpstan.neon
composer test-psalm    # Psalm, settings/psalm.xml
composer test-unit     # PHPUnit unit tests, settings/phpunit.xml
```

Or all at once: `composer test` (inside the container: `make composer_test`).

- New behavior in `src/` should come with unit tests under `tests/unit/` mirroring the
  namespace/path of the code under test. Test classes use the `Tests\Unit\Movary\`, e.g.
  `src/Domain/Movie/Foo.php` is tested by `tests/unit/Domain/Movie/FooTest.php` in 
  `Tests\Unit\Movary\Domain\Movie`.
- Never delete or weaken an existing test to make it pass. If a test seems wrong, say so
  explicitly and let a human decide.
- If you changed the REST API, update `docs/openapi.json` accordingly (see
  `docs/development/setup.md`).

## 4. Code style

- Follow the existing style in the file/module you're editing; don't introduce a new style.
- PHP code style is enforced by `settings/phpcs.xml`/`settings/phpstorm.xml` — run
  `composer test-cs` rather than guessing formatting.
- Match existing patterns for dependency injection, repositories, and domain services
  instead of introducing new architectural patterns (e.g. don't introduce a new ORM,
  templating engine, or DI container).
- Do not add comments/documentation beyond what's needed to explain non-obvious decisions,
  and never remove existing comments unless they're factually wrong.
- Raw SQL must work on **both** MySQL and SQLite. Avoid backend-specific functions (e.g.
  `YEAR()`, `DATE_SUB()`, `strftime()`, `datetime()`) unless you branch on
  `$this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform` like the existing
  repositories do. Passing tests on the default SQLite setup does not prove MySQL works.

## 5. What AI agents should and should not do autonomously

**Good fits for agent work (small, verifiable, low blast radius):**

- Fixing a well-described, reproducible bug with a clear root cause.
- Adding a unit test for existing untested behavior.
- Small, localized refactors requested explicitly by a maintainer.
- Documentation fixes/updates in `docs/`.
- Adding a small, additive feature to an existing domain/controller that follows an
  established pattern in the codebase.

**Require explicit human design/approval before implementation:**

- Database schema/migration changes (`db/migrations/**`).
- Authentication, authorization, session, or token handling
  (`src/Domain/User/Service/Authentication.php` and related middleware).
- Changes to third-party integrations that move/delete user data (import/export, Trakt,
  Letterboxd, Plex/Jellyfin/Emby webhooks).
- Anything touching Docker/build/CI pipeline files, dependency version bumps, or
  `composer.json`/`composer.lock`.
- Introducing new dependencies, frameworks, or build tools.

If a request falls in the second bucket, propose a short plan/diff for review instead of
implementing it outright.

## 6. PR / commit expectations

- Keep commits/PRs focused on one logical change.
- Use short, descriptive branch names (e.g. `fix-user-selection`, `update-dependencies`) and
  imperative-mood commit messages (e.g. "Fix X", "Add Y") — this repo doesn't enforce a
  Conventional Commits format, just match the existing git history style.
- PR description should state: the problem, the root cause (for bugs), the approach taken,
  what was tested/verified and how, and any follow-ups deliberately left out of scope.
- Call out anything you're unsure about explicitly (e.g. "I assumed X because Y — please
  confirm").
- Link to the relevant Github issue/discussion when one exists.

## 7. Getting help / escalation

- If requirements are ambiguous, open a Github discussion or ask in the PR rather than
  guessing (see `README.md` Support section for community channels).
- If a fix requires touching many files or changing established architecture, stop and
  propose a plan first instead of executing a large autonomous rewrite.
