# Decisions log

## 2026-09-20 — Git migration baseline

- Repository is the future source of truth; old local paths are historical only.
- Commit only shared source and sanitized datasets supplied in this task.
- Exclude WordPress core, third-party plugins, full install archives, backups, cache/logs and generated thumbnails.
- Record missing six demo packages and marketplace assets rather than fabricating them.
