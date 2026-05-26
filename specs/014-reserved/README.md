# Spec 014 — Reserved

## Status: Intentionally Vacant

The specification number `014` is **reserved and intentionally left vacant**.

### Rationale

The TSiSIP specification sequence advances from:
- `013-brownfield-follow-up`
- `015-auto-tls-certificate-rotation`

No feature was ever assigned to `014`. This gap is documented and accepted.
Scripts or tooling that iterate `specs/001/` through `specs/024/` should treat
`014-reserved/` as a no-op entry.

### History

- 2026-05-26: Gap documented in `docs/aide/feedback-loop.md` (Issue P4).
- 2026-05-26: Reserved directory created to satisfy sequential iterators.

### Future Use

If a new feature is introduced between the brownfield follow-up (013) and
the auto-TLS certificate rotation (015) scopes, it may be assigned to `014`.
Until then, this directory remains empty except for this README.
