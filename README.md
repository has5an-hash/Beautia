# Beautia

Source of truth for the Beautia premium Persian RTL WordPress theme. The product has seven independent beauty-business demo datasets: Nail, Clinic, Hair, Spa, Lashes, Makeup, and Barber.

## What is in this repository

- `core/beautia-theme-source`: the actual shared theme source extracted from the supplied Nail installation.
- `core/beautia-core`: the supplied companion plugin source.
- `database`: seven **sanitized** independent SQL datasets. Development users, OTP transients and SMS logs were removed before commit.
- `reference`: Persian project context, handoff, work log, checkpoint and marketplace standards supplied with this migration.
- `demos`: inventory and handoff notes. Only Nail's complete installation archive was supplied in this migration; the six remaining file packages must be added later.

## Deliberately excluded

WordPress core, third-party plugin copies (Akismet and Duplicator), cache, logs, backups, generated WordPress thumbnails, local `wp-config.php`, and full install archives are not source-controlled. See `MIGRATION-GUIDE.md`.

## Start here

1. Read `CURRENT-STATUS.md` and `AGENT-CONTINUATION-PROMPT.md`.
2. Read `reference/PROJECT-HANDOFF-FA.md` and `reference/CHECKPOINT.json`.
3. Use `database/README.md` only in a disposable local or cloud development database.

This repository is not a marketplace release package and has not passed the final RTL Theme release gate.
