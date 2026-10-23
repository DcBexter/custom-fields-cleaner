# Custom Fields Cleaner

WordPress admin tool for removing empty and orphaned post metadata with a chosen field-name prefix. No ACF dependency.

## Compatibility

- Tested with **WordPress 7.1.3**, **PHP 8.2–8.5** and **MariaDB 11.8**.
- Requires WordPress 7.1+ and PHP 8.2+.
- CI tests the latest stable WordPress on every PR, push to master and weekly. Older WordPress, ACF-specific field-definition cleanup and multisite network-wide cleanup are not covered.

## Install and use

1. Back up your database and try on staging.
2. Download this repository, extract it into `wp-content/plugins/custom-fields-cleaner/`, then activate **Custom Fields Cleaner**.
3. Open **Tools → Custom Fields Cleaner** as an administrator.
4. Enter a field prefix of at least 3 bytes, such as `CF_`. Matching is case insensitive; `_` and `%` are literal characters.
5. Choose **Orphans only**, **Empty only** or **Clean all**, then **Check** to preview.
6. Type `clean`, then **Clean** to permanently delete matching rows.

**Empty** means an exact empty string. Values `0`, whitespace, serialized values and nonempty fields on existing posts remain.
**Orphan** means the parent post no longer exists. Orphaned matching rows are deleted even when their values are nonempty; deleting an ACF field definition alone is not sufficient.
Both filters apply the field prefix. ACF reference keys such as `_CF_title` remain unless your prefix explicitly matches them.

Only the active site’s WordPress post metadata table is used. Custom database-table selection and prefix cookies were removed to prevent cross-table deletion. No automatic database cleaning runs.

## Development and updates

No build step or runtime packages. CI uses WP-CLI to install a disposable WordPress database and runs:

```sh
wp plugin activate custom-fields-cleaner
wp eval-file wp-content/plugins/custom-fields-cleaner/tests/integration.php
```

Run tests only against a throwaway database: fixtures deliberately create and delete metadata.
Checks cover deletion scope, preserved values, filters, nonce/capability checks, escaped output, cache invalidation and database errors.

Renovate updates GitHub Actions and CI service images. Minor, patch, pin and digest PRs may auto-merge after required CI; major updates require human review.
Dependabot handles available security updates. These workflows do not publish plugin releases or deploy WordPress sites.
Replace plugin files manually to update; GitHub is not a WordPress automatic-update source.

## 1.5.1

- Use WordPress’s database connection and metadata deletion API.
- Enforce administrator capability, nonce and confirmation; escape output and prefix queries.
- Restrict orphan cleanup to the selected literal field prefix.
- Replace outdated setup instructions with current installation and test steps.

[MIT](LICENSE.txt)
