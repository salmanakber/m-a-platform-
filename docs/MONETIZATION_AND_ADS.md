# Monetization, advertising & multi-location — implementation note

Status: **not built as a billing module**. Existing MVP already has promotion slots and multi-office storage. Advertising placements do **not** exist yet.

## What already exists

| Feature | Status |
|---------|--------|
| Promoted directory slots per canton | Yes — `promotions`, `promotion_positions`, waitlist, invoice link |
| Slot count | Extended to **5** positions per canton (was 3 in original SOW decision) |
| Activation after paid invoice | Yes — `PromotionService::activateAfterPayment` |
| Expiry → waitlist promotion | Yes — `expirePromotion` |
| Multiple office locations | Yes — `expert_offices` (primary + additional) |
| Extra-location pricing / billing | **No** |
| Ad placements (logo + link banners) | **No** model, routes, or UI |

## Promoted listings (existing + small extension)

- **Distinction:** Active promotions sort ahead of organic listings (`DirectoryController` / map markers use `promoted` flag + CSS `is-promoted`).
- **Allocation:** Up to 5 slots per canton (`position_number` 1–5). First free slot may be requested; occupied slots go to waitlist.
- **Visibility:** Only `status = active` and within `starts_at`/`ends_at` should appear promoted (expiry job clears overdue).
- **Payment:** Pending until invoice marked paid; unpaid promotions do not sort as promoted.

## Advertising placements (needs separate approval)

Required if client wants ad logos/links:

1. `ad_placements` table: name, image_path, link_url, placement_key (e.g. `directory_sidebar`, `home_strip`), starts_at, ends_at, is_active, sort_order.
2. Admin CRUD under existing admin layout.
3. Public partials that render active placements only.
4. Optional invoice/payment link — **out of scope** without a billing product decision.

## Multiple locations & pricing (needs separate approval)

- **Storage/display:** Already via `expert_offices`; public profile and map iterate all offices.
- **Pricing:** Would need a setting (e.g. CHF per extra office / month), invoice line items, and enforcement of free vs paid office count. Not present today — do not invent charges without client confirmation.

## Clarifications needed from client

1. Confirm **5** promoted positions (vs original decision of **3**).
2. Confirm whether advertising is MVP or post-MVP.
3. Confirm free allotment of office locations (e.g. 1 included, extras billed) and price.
