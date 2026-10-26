## 1.1.0 - unreleased
### Added
- Compatibility with Nextcloud 30 to 34
- Database indexes for faster sync
- Tests in GitHub Actions: lint, Psalm, PHPUnit and an OAuth/sync
  integration test on SQLite, MariaDB and PostgreSQL

### Changed
- OAuth 1.0a is implemented in plain PHP; the PECL oauth extension is
  no longer required
- Requires PHP 8.1 or later; ownCloud is no longer supported
- Token list works without jQuery; pages support the dark theme

### Fixed
- Users could delete other users' access tokens, and token deletion
  was not protected against CSRF
- Note titles were not HTML-escaped in the note and notebook pages
- Plain text (reStructuredText) export failed on PHP 8
- API requests with another user's access token caused a server error
- Tomdroid could not finish authorization: the browser blocked the
  redirect to its tomdroid:// callback

## 1.0.1 = 2022-01-03
### Changed
- Signature issue

## 1.0.0 - 2021-12-27
### Changed
- Ownership takeover
- Compatibility Nextcloud 23
