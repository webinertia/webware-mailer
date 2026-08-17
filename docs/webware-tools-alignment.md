# Webware Tools Alignment — webware-mailer CI/CD

## Purpose

Align `webware/webware-mailer` CI/CD pipeline and dev-tooling configuration with
`webware/webware-log` (the reference implementation attached to this workspace).
Both packages are tooled by the `webware/webware-tools` reusable workflow
(`webinertia/webware-tools@0.1.x`); webware-log is the canonical consumer, and
webware-mailer adopts the same shape with mailer-specific inputs.

**Scope:** CI/CD pipeline, tooling configs, composer metadata, baseline files,
and the minimum test scaffolding required for a green pipeline.

**Explicitly out of scope (per user):**
- PHPStan — `phpstan.neon.dist`, `stubs/`, `tomasvotruba/type-coverage`, any
  PHPStan composer dependency. Leftover in webware-log; not part of this
  alignment. (The reusable workflow runs no PHPStan job either.)
- Local Docker dev tooling: `bin/install-deps.sh`, `compose.yml`, `docker/`
  (not referenced by CI).
- Writing the full test suite for all 17 classes; only the scaffolding /
  minimum required to keep the pipeline green is in scope (see Decisions).

## Current state vs. target

webware-mailer today (branch `0.1.x`): tracked `LICENSE` only; `composer.json`,
`src/` (17 classes), `.gitattributes`, `.gitignore` all untracked. No `.github/`,
no `phpunit.xml.dist`, no tests, no baselines, no `composer.lock`, no CI.

| Artifact | webware-log (reference) | webware-mailer (current) | Action |
|---|---|---|---|
| `.github/workflows/continuous-integration.yml` | wrapper calling webware-tools reusable workflow | absent | create |
| `.github/copilot-instructions.md` | present | absent | port |
| `phpunit.xml.dist` | PHPUnit 13.1 schema, strict flags | absent | create |
| `mago.toml` + baselines | extends webware-tools, baselines | absent | create |
| `infection.json5.dist` | mago as staticAnalysisTool | absent | create |
| `codecov.yml` | present | absent | copy |
| `renovate.json` | `local>webinertia/.github:renovate-config` | absent | copy |
| `phpbench.json.dist` | present (no benchmarks dir yet) | absent | copy (config only) |
| `composer.lock` | committed | absent | generate + commit |
| `composer.json` scripts | `test`, `test-coverage`, `test-integration`, `mutation-test` | partial | align |
| PHPUnit version | `^13.3.0` | `^12.5.33` | bump |
| `infection/infection`, `phpbench/phpbench`, `roave/backward-compatibility-check` | require-dev | absent | add |
| test suites (`unit test`, `integration test`) | both defined + populated | no `test/` dir at all | create scaffolding |
| `.gitattributes` / `.gitignore` | present | **already in place by user, matches webware-log** | verify only |

## Reference mechanics (what the reusable workflow expects)

`webinertia/webware-tools/.github/workflows/continuous-integration.yml@0.1.x`
exposes `workflow_call`:

- **Inputs:** `php-versions` (JSON array), `run-integration` (bool),
  `composer-options`, `db-image`, `db-env-json`, `db-port`, `db-health-cmd`,
  `db-health-retries`, `db-health-interval-seconds`, `enable-codecov` (bool),
  `enable-infection` (bool), `coverage-php-version`, `min-msi`,
  `min-covered-msi`, `test-env-json`.
- **Secrets:** `CODECOV_TOKEN` (optional), `INFECTION_DASHBOARD_API_KEY`
  (optional) — both forwarded via `secrets: inherit`.
- **Jobs:**
  1. **mago** — matrix over `php-versions`; `composer install`;
     pins `MAGO_PHP_VERSION` per matrix leg; runs `mago format --check`,
     `mago lint`, `mago analyze`, `mago guard` (each `success() || failure()`).
  2. **test** — matrix `php-versions` × `[lowest, locked, latest]`;
     composer install/update per strategy; optional DB container (omitted when
     `db-image` empty); exports `test-env-json` overrides; runs `composer test`
     (non-canonical legs) or `composer test-coverage` (canonical leg:
     `coverage-php-version` + `locked`, pcov); `composer test-integration` when
     `run-integration`; uploads `clover.xml` artifact.
  3. **codecov** — needs `test`; `codecov/codecov-action@v5`,
     `slug: ${{ github.repository }}`, `files: clover.xml`,
     `fail_ci_if_error: false` (report-only).
  4. **mutation-test** — needs `test`; PHP `coverage-php-version` with pcov +
     `tools: mago`; `composer mutation-test -- --min-msi=…
     --min-covered-msi=… --logger-github`; Infection itself invokes Mago via
     `staticAnalysisTool` in `infection.json5.dist`.

Implications for the consumer repo:

- `composer test` / `test-coverage` / `test-integration` / `mutation-test`
  scripts must exist.
- `phpunit.xml.dist` must define suites named `unit test` and
  `integration test`.
- `composer.lock` must be committed (the `locked` matrix leg).
- `mago.toml` must be present (format/lint/analyze/guard all run against it).
- A DB container is **not** used for mailer (`db-image` omitted ⇒ both DB steps
  skipped at zero cost).
- Pipeline is red until at least one test exists — PHPUnit 13 errors on zero
  executed tests, and Infection cannot score an empty suite.

## Work items

### 1. `composer.json`

- `require.php`: keep `~8.4.1 || ~8.5.0`. Do **not** add `8.6.0-dev` — it is
  a leftover artifact in webware-log and will be removed there separately
  (webware-log needs its own branch for that cleanup; out of scope here).
- `require-dev`:
  - bump `phpunit/phpunit` `^12.5.33` → `^13.3.0`
  - add `infection/infection: ^0.34.1`
  - add `phpbench/phpbench: ^1.7`
  - add `roave/backward-compatibility-check: ^8.21.0`
  - keep `psr/http-*`, `webware/messagebus-event`, `webware/webware-tools`,
    `roave/security-advisories` as-is.
- `config.platform.php`: `8.4.1` → `8.4.99` (match webware-log resolution;
  unaffected by the `8.6.0-dev` removal).
- `autoload-dev`: change `WebwareTest\Mailer\` mapping from `test/` to
  `test/unit/`; add `WebwareTestIntegration\Mailer\` → `test/integration/`
  (mirrors `WebwareTest\Log\` / `WebwareTestIntegration\Log\` split).
- `scripts` (align with webware-log):
  - `test`: `phpunit --no-coverage --colors=always --testsuite "unit test"`
  - `test-coverage`: `phpunit --colors=always --coverage-clover clover.xml
    --coverage-html coverage/html --coverage-text`
  - `test-integration`: `phpunit --no-coverage --colors=always --testsuite
    "integration test"`
  - add `mutation-test`: `infection`
- No PHPStan package, no PHPStan script.

### 2. `phpunit.xml.dist` (new)

Modeled on webware-log, minus DB specifics:

- schema 13.1 (`https://schema.phpunit.de/13.1/phpunit.xsd`),
  `bootstrap="vendor/autoload.php"`, `colors="true"`,
  `cacheDirectory=".phpunit.cache"`.
- Strict flags: `requireCoverageMetadata="true"`, `failOnNotice="true"`,
  `failOnDeprecation="true"`, `failOnWarning="true"`.
- Testsuites: `unit test` → `test/unit`; `integration test` → `test/integration`.
- `<source restrictNotices="true" ignoreIndirectDeprecations="true">` including
  `src`.
- No `<extensions>` bootstrap (webware-log's `ListenerExtension` is MySQL-seed
  specific) and no `<env>` vars.

### 3. Mago tooling

- `mago.toml` (new):
  - `extends = "vendor/webware/webware-tools/mago.toml"`
  - `php-version = "8.4.1"`
  - `[linter] baseline = "lint-baseline.toml"`;
    `[analyzer] baseline = "analysis-baseline.toml"`
  - `[source] paths = ["src", "test"]`, `includes = ["vendor"]`
- `lint-baseline.toml` + `analysis-baseline.toml` (new): start empty; populate
  only with issues accepted as intentional after the fix pass. Suppressions
  require explicit user approval per issue.
- Fix pass: `mago format`, then `mago lint` + `mago analyze` + `mago guard`;
  fix all findings in `src/`; baseline only the approved remainder.

### 4. `infection.json5.dist` (new)

Copy webware-log structure: `source.directories = ["src"]`, `timeout = 10`,
`threads = "max"`, logs `text: infection.log`, `summary: summary.log`,
`stryker` badge regex `/^\d+\.\d+\.x$/`, `mutators: {"@default": true}`,
`staticAnalysisTool: "mago"`.

### 5. `codecov.yml` (new)

Copy webware-log verbatim (project/patch targets `auto`, threshold `0%`,
comment layout `diff, flags, files`).

### 6. `renovate.json` (new)

Copy webware-log verbatim:
`"extends": ["local>webinertia/.github:renovate-config"]`.

### 7. `phpbench.json.dist` (new)

Copy webware-log (`runner.path: benchmarks`, `*Bench.php`). Config only — no
`benchmarks/` directory and no CI job; webware-log ships it the same way.

### 8. `.github/workflows/continuous-integration.yml` (new wrapper)

Mirror webware-log's wrapper with mailer inputs:

- `on`: `pull_request` → branches `[0-9]+.[0-9]+.x`; `push` → same branches +
  tags `[0-9]+.[0-9]+.[0-9]+`. (No push to default implicitly; branch
  protection blocks direct pushes to release branches, same rationale as
  webware-log.)
- `uses: webinertia/webware-tools/.github/workflows/continuous-integration.yml@0.1.x`
- `secrets: inherit`
- `with`:
  - `php-versions: '["8.4", "8.5"]'`
  - `run-integration: true` — integration suite will exercise a Mailpit
    container (local `docker-compose` setup with Mailpit is built in a later
    step). In CI, the reusable workflow's generic `db-image` step can host
    Mailpit (image + port + health command TBD at the test-suite step); until
    integration tests exist the `test-integration` leg runs an empty suite.
  - `enable-codecov: true`
  - `enable-infection: true` — configured now; the test-suite step immediately
    follows this alignment work.
  - `coverage-php-version: "8.5"` (canonical leg, highest supported PHP)
  - `min-msi: "95"`, `min-covered-msi: "95"` (start at webware-log's values)
  - omit `db-image`, `db-env-json`, `db-port`, `db-health-cmd`,
    `test-env-json` for now (defaults; Mailpit wiring lands with the
    integration suite).

### 9. `.github/copilot-instructions.md` (new)

Port webware-log's file verbatim: PHPUnit 13 mock-vs-stub rules
(`createStub()` for value-returning doubles, `createMock()` only with
`expects()`) and `requireCoverageMetadata="true"` rules
(`#[CoversClass]` / `#[CoversMethod]` per test class).

### 10. Test scaffolding

Full test suite is a **later step** (per D1). This alignment step only lays
out the structure so tooling works end-to-end:

- Create `test/unit/` and `test/integration/` directories (empty,
  `.gitkeep`-held).
- Composer `autoload-dev` namespaces updated accordingly (work item 1).
- **Accepted risk:** CI `test` and `mutation-test` jobs fail on empty suites
  until the test-suite step lands (immediately following). The `mago` and
  `codecov` jobs are independent of test content.

### 11. `composer.lock`

Generate against platform `php 8.4.99` and commit. Required by the `locked`
matrix leg and by `webware/webware-tools` dev-dependency pinning.

### 12. `.gitattributes` / `.gitignore`

Already in place (user-added) and already byte-equivalent in content to
webware-log's. Verify only; no changes planned.

## Repository / org settings (manual, done by user)

- `INFECTION_DASHBOARD_API_KEY` secret in `webware/webware-mailer` repo
  settings (per-repo; feeds the Stryker dashboard + badge via
  `secrets: inherit`).
- `CODECOV_TOKEN` — org-wide secret in `webinertia` org, already used by
  webware-log; inherits through. Confirm Codecov App has access to
  `webware-mailer`.
- Renovate: org-wide config `webinertia/.github` already referenced by
  webware-log; verify the Renovate App has `webware-mailer` enabled.
- Optional: branch protection on `0.1.x` (blocks direct pushes; enables the
  PR-only trigger rationale in the wrapper).

## Decisions (resolved)

| # | Decision | Resolution |
|---|---|---|
| D1 | Unit tests now vs later | **Later** — full test suite is the next step after this alignment work. Scaffold `test/unit/` + `test/integration/` only. |
| D2 | `run-integration` | **`true`** — integration tests will use a Mailpit-backed `docker-compose` setup (built in the test-suite step); CI container wiring via the reusable workflow's `db-image` step, TBD then. |
| D3 | `enable-infection` | **`true`** now; next step is the test suite. |
| D4 | `min-msi` / `min-covered-msi` | **`95` / `95`** from the start. |
| D5 | `8.6.0-dev` in `require.php` | **Do not adopt.** Leftover in webware-log; will be removed there in separate repo work (webware-log needs its own branch). `platform.php` still moves to `8.4.99`. |
| D6 | `phpbench.json.dist` without `benchmarks/` | Yes (config-only parity, same as webware-log). |
| D7 | Plan file location | `docs/webware-tools-alignment.md` in webware-mailer (this file). |

## Execution order

1. This plan reviewed + decisions D1–D7 confirmed. (Done.)
2. `composer.json` updates; run `composer update`; commit `composer.lock`.
3. Test scaffolding: create `test/unit/` + `test/integration/` (empty,
   `.gitkeep`-held); full suite in the following step.
4. Tool configs: `phpunit.xml.dist`, `mago.toml`, baselines, `infection.json5.dist`,
   `codecov.yml`, `renovate.json`, `phpbench.json.dist`.
5. Mago pass: `mago format` (isolated commit), then lint/analyze/guard fixes;
   baseline only approved remainders.
6. `.github/workflows/continuous-integration.yml` wrapper (with D2–D4 inputs) +
   `.github/copilot-instructions.md`.
7. Local verification before push:
   - `composer validate --strict`
   - `mago format --check`, `mago lint`, `mago analyze`, `mago guard` (green)
   - `composer test` / `test-integration` / `test-coverage` / `mutation-test`
     expected **red** (empty suites) — accepted until the test-suite step.
8. Push branch, open PR against `0.1.x`; verify `mago` and `codecov` jobs
   green; `test` + `mutation-test` go green when the test-suite step lands.
9. User-side repo settings (INFECTION token, Codecov/Renovate app access,
   optional branch protection).

## Explicitly excluded (reminder)

- PHPStan (`phpstan.neon.dist`, stubs, type-coverage extension, PHPStan deps).
- `bin/install-deps.sh`, `compose.yml`, `docker/`.
- Benchmarks (`benchmarks/*Bench.php`) — config only.
