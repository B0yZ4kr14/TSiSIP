# TSiSIP Typography

## Typeface Selection

### Primary: Inter

Inter is a typeface carefully crafted for computer screens. Its tall x-height and open apertures ensure excellent readability at small sizes — critical for dense SIP data tables.

- Weights used: 400 (regular), 500 (medium), 700 (bold)
- Never use weights below 400 for UI text

### Monospace: JetBrains Mono

JetBrains Mono features increased letter height for better readability of code and numeric data. Used exclusively for SIP URIs, IP addresses, HA1 hashes, and dispatcher weights.

## Scale

| Token | Size | Line Height | Usage |
|---|---|---|---|
| text-xs | 0.75rem (12px) | 1.5 | Badges, timestamps |
| text-sm | 0.875rem (14px) | 1.5 | Table cells, buttons |
| text-base | 1rem (16px) | 1.5 | Body text, inputs |
| text-lg | 1.125rem (18px) | 1.5 | Section headings |
| text-xl | 1.25rem (20px) | 1.4 | Card titles |
| text-2xl | 1.5rem (24px) | 1.3 | Page headings |
| text-3xl | 1.875rem (30px) | 1.2 | Hero titles |

## Rules

- Minimum font size in UI: 12px (text-xs)
- Headings use font-weight 700; UI labels use 500
- Monospace is never used for body text
- Line length should not exceed 75 characters for readability
