# Feature Specification: Webware-Tools Alignment

**Feature Branch**: `001-webware-tools-alignment`

**Created**: 2026-08-18

**Status**: Implemented (reference instance)

**Input**: Align package CI/CD pipeline and dev tooling with the `webinertia/webware-tools`
reusable workflow, matching the shape of the canonical reference consumer, with package-specific
inputs.

## Purpose

Every Webware package runs the same CI/CD pipeline and dev tooling, owned centrally by
`webware/webware-tools` and consumed through a thin per-package wrapper. This spec defines the
consumer-side contract: which artifacts a package must provide, what shape they take, and which
values are package-specific parameters.

This document doubles as the copy-into-project template. A new package copies this spec, fills in
the Package Parameters section, and executes the plan/tasks. This repository (webware-mailer) is
the reference implementation of the already-completed alignment.

## Scope

**In scope:** CI/CD pipeline, tooling configs, composer metadata, baseline files, and the minimum
test scaffolding required for a green pipeline.

**Out of scope (explicit):**

- PHPStan: `phpstan.neon.dist`, `stubs/`, type-coverage packages. The reusable workflow runs no
  PHPStan job.
- Local Docker dev tooling: install scripts, `compose.yml`, `docker/` (not referenced by CI).
- Full test suite coverage; only scaffolding sufficient to keep the pipeline green is required.

## Package Parameters

Copy this spec into a package and replace these values. webware-mailer's values are the reference
instance.

| Parameter | webware-mailer (reference) |
|---|---|
| `php-versions` | `["8.4", "8.5"]` |
| `require.php` | `~8.4.1 \|\| ~8.5.0` |
| `config.platform.php` | `8.4.99` |
| `run-integration` | `true` |
| `enable-codecov` | `true` |
| `enable-infection` | `true` |
| `coverage-php-version` | `8.5` |
| `min-msi` / `min-covered-msi` | `95` / `95` |
| DB container (`db-image`) | omitted (no database) |
| Integration container | Mailpit (wired with integration suite) |
| Test autoload namespaces | `WebwareTest\Mailer\` → `test/unit/`, `WebwareTestIntegration\Mailer\` → `test/integration/` |

## User Scenarios & Testing

### User Story 1 - Maintainer opens a PR and gets a full pipeline (Priority: P1)

A maintainer opens a pull request against a release branch. The wrapper workflow triggers the
reusable workflow, which runs Mago checks, the test matrix (lowest/locked/latest dependency
strategies), integration tests, Codecov upload, and Infection mutation testing.

**Why this priority**: The pipeline is the deliverable; nothing else in this spec has value
without it.

**Independent Test**: Open a PR touching `src/`; all CI jobs are scheduled and pass on a clean
change.

**Acceptance Scenarios**:

1. **Given** a PR against a `X.Y.x` branch, **When** pushed, **Then** Mago, test, codecov, and
   mutation-test jobs all run.
2. **Given** a PR with a Mago lint finding, **When** pushed, **Then** the Mago job fails and
   reports the finding.
3. **Given** a change that drops a test suite to zero tests, **When** pushed, **Then** the test
   job fails (PHPUnit errors on zero executed tests).

### User Story 2 - New package adopts alignment by copying this spec (Priority: P2)

A maintainer copies the spec/plan/tasks into another Webware package, fills in Package Parameters,
and executes the tasks to get the same pipeline.

**Why this priority**: The reusable-template use case; keeps every package aligned at low cost.

**Independent Test**: In a fresh package, follow plan + tasks; resulting files match the
reference instance here modulo Package Parameters.

**Acceptance Scenarios**:

1. **Given** a package without `.github/`, **When** tasks are executed, **Then** wrapper
   workflow exists and points at the reusable workflow with package inputs.
2. **Given** an unaligned `composer.json`, **When** aligned, **Then** required scripts and
   require-dev entries match the reference.

### User Story 3 - Tooling updates propagate with a version bump (Priority: P3)

When `webware/webware-tools` releases a new workflow version, consumers update the pinned ref and
regenerate baselines, without rewriting per-package config.

**Why this priority**: Ongoing maintenance loop; lower priority because initial alignment works
without it.

**Independent Test**: Bump the pinned workflow ref; pipeline still green after `mago` fix pass.

**Acceptance Scenarios**:

1. **Given** a new webware-tools version, **When** the wrapper ref is bumped, **Then** only the
   wrapper and possibly baselines change.

### Edge Cases

- No `db-image` set: both DB steps of the reusable workflow are skipped at zero cost.
- Integration tests do not exist yet: `test-integration` leg runs an empty suite; acceptable
  temporarily, but at least one test per suite is required for a green pipeline.
- Zero tests: PHPUnit 13 errors, and Infection cannot score an empty suite; pipeline is red until
  scaffolding tests exist.

## Requirements

### Functional Requirements

- **FR-001**: Repository MUST provide `.github/workflows/continuous-integration.yml` calling
  `webinertia/webware-tools/.github/workflows/continuous-integration.yml` with `secrets: inherit`
  and package-specific inputs.
- **FR-002**: `composer.json` MUST define scripts `test`, `test-coverage`, `test-integration`,
  and `mutation-test`.
- **FR-003**: `phpunit.xml.dist` MUST use PHPUnit 13.1 schema, strict flags
  (`requireCoverageMetadata`, `failOnNotice`, `failOnDeprecation`, `failOnWarning`), and suites
  named `unit test` and `integration test`.
- **FR-004**: `mago.toml` MUST extend `vendor/webware/webware-tools/mago.toml` and define
  `php-version`, baseline paths, and source paths.
- **FR-005**: `lint-baseline.toml` and `analysis-baseline.toml` MUST start empty; entries only for
  maintainer-approved intentional suppressions.
- **FR-006**: `infection.json5.dist` MUST configure `source.directories = ["src"]` and
  `staticAnalysisTool: "mago"`.
- **FR-007**: `codecov.yml` MUST match the reference consumer (targets `auto`, threshold `0%`).
- **FR-008**: `renovate.json` MUST extend `local>webinertia/.github:renovate-config`.
- **FR-009**: `phpbench.json.dist` MUST exist with runner config; a `benchmarks/` directory is not
  required.
- **FR-010**: `composer.lock` MUST be committed (locked matrix leg).
- **FR-011**: `.github/copilot-instructions.md` MUST carry PHPUnit 13 mock-vs-stub and coverage
  metadata rules.
- **FR-012**: Each test suite MUST contain at least one test.
- **FR-013**: `README.md` MUST carry the standard badge set (PHP version, latest version,
  license, CI, codecov, mutation testing) with CI/codecov badges tracking the default branch and
  the Stryker badge updated whenever the default branch changes.
- **FR-014**: Repository MUST add spec-kit scaffolding directories (`/.specify/`, `/specs/`) to
  `.gitattributes` `export-ignore` so distro packages exclude them.

### Key Entities

- **Wrapper workflow**: per-package file translating package parameters into reusable workflow
  inputs.
- **Reusable workflow**: `webinertia/webware-tools@X.Y.x`; owns job definitions (mago, test,
  codecov, mutation-test).
- **Baselines**: per-package TOML files holding approved Mago suppressions.

## Success Criteria

### Measurable Outcomes

- **SC-001**: All CI jobs green on the canonical branch: Mago (all versions), test matrix,
  codecov, mutation-test.
- **SC-002**: `mago format --check`, `mago lint`, `mago analyze`, `mago guard` report zero
  unbaselined issues.
- **SC-003**: Infection MSI and covered MSI at or above package thresholds (95 reference).
- **SC-004**: Codecov receives coverage upload from exactly one matrix leg (canonical:
  `coverage-php-version` + locked).
- **SC-005**: A fresh package reaches SC-001..SC-004 by executing this spec's plan and tasks
  without modifying the reusable workflow.

## Assumptions

- The reusable workflow `@0.1.x` keeps the inputs listed in the reference mechanics until a
  deliberate version bump.
- No database container is used; packages needing one set `db-image` and related inputs.
- Test scaffolding (not full test coverage) is sufficient for alignment scope.
