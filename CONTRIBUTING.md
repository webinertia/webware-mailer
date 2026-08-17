# Contributing

## Issues

- Search [existing issues](https://github.com/webinertia/webware-mailer/issues)
  before opening a new one.
- Include PHP version, package version, and a minimal reproduction.

## Pull Requests

- Base PRs on the current versioned release branch (e.g. `0.1.x`).
- Keep PRs focused: one concern per PR.
- CI must pass before merge: Mago (format, lint, analyze, guard), PHPUnit unit
  and integration suites, Codecov upload, and Infection mutation testing.

## Local Setup

```bash
composer install
```

## Checks

```bash
composer test                 # unit suite
composer test-integration     # integration suite (in-process SMTP server)
XDEBUG_MODE=coverage composer test-coverage
composer mutation-test -- --min-msi=95 --min-covered-msi=95
mago format --check
mago lint
mago analyze
mago guard
```

## Test Conventions

- PHPUnit 13 with `requireCoverageMetadata="true"`: every test class declares
  `#[CoversClass]` and `#[CoversMethod]` (or `#[CoversTrait]` for traits).
- Value-returning test doubles use `createStub()`. `createMock()` is used only
  when behavior is verified with `expects()`.
- Method names are descriptive (`sendThrowsWithoutAdapter`) and marked with
  `#[Test]`.

## Coding Standards

Formatting and static analysis are enforced by
[`webware/webware-tools`](https://github.com/webinertia/webware-tools) via Mago.
Run `mago format` before committing. Any accepted suppression or baseline entry
must be approved by a maintainer.

## TODO Comments

Tag TODO comments with a GitHub username or issue reference, e.g.
`// TODO(@username): ...`.
