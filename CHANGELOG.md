# Changelog

## [Unreleased]

## [1.0.0] - 2026-11-13

The first stable release. Omnishield Disposable: is this e-mail's domain a disposable one - the free disposable-email-domains list (CC0) shipped in the package, an allow list and a deny list of the site's, subdomains included; no call while a request runs, a command to refresh the list.

### Changed

- Requires `omnisense/contracts: ^1.0`, `omnisense/omnishield: ^1.0`: stable versions, so `composer require omnishield/disposable` installs in a project at the default minimum-stability. `extra.branch-alias` names 1.x (`1.x-dev`); the CI (`.github/workflows/ci.yml`, glitchr-studio/ci's shared workflow) and the release on a tag (`release.yml`).
- `phpstan-baseline.neon`, empty: the shared workflow requires the file; nothing is accepted.
