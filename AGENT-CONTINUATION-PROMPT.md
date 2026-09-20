# Agent continuation brief

## Goal

Finish and release Beautia as a premium Persian RTL beauty-business WordPress theme for RTL Theme, without paid-plugin hard dependencies or reliance on Windows/XAMPP/localhost.

## Architecture

- Shared theme: `core/beautia-theme-source`
- Companion plugin: `core/beautia-core`
- Demo datasets: `database/beautia_<demo>.sql`
- QA evidence: `qa/` and `docs/qa/`
- Persian source-of-truth history: `reference/`

## Current status

Shared source and seven sanitized datasets are committed. Only the Nail full-install archive was supplied to this migration; it was selectively extracted so WordPress core and environment files are not tracked. No final release package or Duplicator restore is present or verified.

## Rules

Preserve demo isolation. Never commit secrets, test users, OTP/SMS logs, cache, backups, WordPress core or generated thumbnails. Never mark a test as complete without fresh evidence. Check current RTL Theme rules before marketplace work.

## Read first

1. `CURRENT-STATUS.md`
2. `reference/PROJECT-HANDOFF-FA.md`
3. `reference/CHECKPOINT.json`
4. `docs/qa/QA-STATUS.md`
5. `database/README.md`

## First recommended task

Provision a clean cloud WordPress environment, restore the Nail dataset from repository source, then perform the outstanding real-pointer hover-intent test across all demos before any marketplace packaging work.
