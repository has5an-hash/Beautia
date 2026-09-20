# Migration guide

## Rebuild a development instance

1. Provision a clean WordPress environment in a cloud/dev container.
2. Copy `core/beautia-theme-source` to `wp-content/themes/beautia`.
3. Copy `core/beautia-core` to `wp-content/plugins/beautia-core` and activate it.
4. Install required public plugins at known versions only after confirming compatibility. The supplied Nail install shows Duplicator 1.5.17 and Akismet 5.7.2, but neither is committed here.
5. Import exactly one matching sanitized SQL file. Follow `database/README.md`.
6. Run a serialized URL replacement from the former localhost URL to the target URL, set `home` and `siteurl`, then re-save permalinks.
7. Create a new administrator; no development user is retained in these dumps.

## What must be supplied next

The exact current file packages for Clinic, Hair, Spa, Lashes, Makeup and Barber; their intentional media/uploads; final marketplace assets; and seven verified Duplicator installer/archive pairs.
