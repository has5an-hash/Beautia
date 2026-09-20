# Development rules

1. Treat this repository, not a local XAMPP installation, as the source of truth.
2. Do not commit WordPress core, `wp-config.php`, credentials, OTP/SMS logs, cache, Duplicator backups, generated thumbnails or environment-specific files.
3. Keep the seven datasets isolated: no cross-demo content, media, settings or fallback assets.
4. Change shared source only in `core/`; document every material change in `docs/decisions/CHANGELOG.md` and update the Persian handoff/checkpoint when it changes project status.
5. Do not claim a QA item, Duplicator restore, installation, marketplace compatibility or license behavior without fresh evidence.
6. For RTL Theme marketplace work, read `reference/استانداردهای-انتشار-محصول-در-راستچین-مرجع-پروژه.md` and verify current official rules before delivery.
