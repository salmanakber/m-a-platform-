# Confirmed project decisions

Updated: 2026-09-29

| # | Topic | Decision |
|---|--------|----------|
| 1 | Framework | Laravel (currently Laravel 9 on PHP 8.0 / XAMPP; upgrade to Laravel 11 when PHP 8.2+ is available) |
| 2 | First build | Full MVP skeleton (all modules) |
| 3 | Buy / Sell | Expert may offer **both**; promotion slots are **shared** (3 per canton, not per Buy/Sell) |
| 4 | Registration fields | Best-effort from available SOW + email templates (no separate Anmeldung form doc) |
| 5 | Initial import | Yes — place `Basis_telsearch.xlsx` in `storage/app/imports/` |
| 6 | Hosting | Decide later |

## Email templates (client)

Seeded as editable `email_templates` records from:

1. Lead notification → expert
2. Customer confirmation
3. Registration welcome → expert

## Runtime note

Homebrew PHP 8.3 is broken on this machine (icu4c). Local development uses:

```bash
export PATH="/Applications/XAMPP/xamppfiles/bin:$PATH"
```
