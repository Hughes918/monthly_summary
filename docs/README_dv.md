# Daily View Documentation Guide

This document is a quick entry point for anyone maintaining the Daily View page.

## Scope

These docs describe the Daily View stack:

- `daily_view.php` (page controller and rendering)
- `daily_view_helpers.php` (data transforms, formatting, utility logic)
- `assets/daily_view.js` (UI interactions, graph rendering, URL state)
- `assets/daily_view.css` (page styling)

## Start Here

1. Read `daily-view.md` for full architecture and data flow.
2. Review request inputs and validation rules.
3. Review dependency map (internal files, APIs, third-party libraries).
4. Use the manual test checklist before and after changes.

## File Responsibilities

- `daily_view.php`
  - Reads query params (`station`, `date`, `type`, `interval`, `panel`, `graph`, `download`)
  - Calls APIs and builds data structures for table/stats/graph
  - Renders page markup and embeds graph JSON config
  - Applies cache-busting query params for `assets/daily_view.css` and `assets/daily_view.js`
  - Includes responsive table attributes (`data-interval`, `data-type`) used by mobile CSS
- `daily_view_helpers.php`
  - Unit conversion, aggregation, formatting, CSV export, shared header/footer rendering
- `assets/daily_view.js`
  - Panel toggles, chart rendering (Chart.js), graph selection state, URL syncing
- `assets/daily_view.css`
  - Visual design for controls/panels/tables/graph controls

## External Dependencies

- Station metadata API:
  - `https://services.cema.udel.edu/internal_services/api/station_metadata/DEOS`
- Subdaily data API:
  - `https://services.cema.udel.edu/internal_services/api/data/{station}/{startDate}/{endDate}?...`
- Chart library:
  - `https://cdn.jsdelivr.net/npm/chart.js`
- Canvas export library:
  - `https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js`
- Analytics:
  - Google gtag script

## Conventions Used in Daily View

- "Valid observations" are flags `1`, `3`, `7`.
- Expected daily completeness is `288` (5-minute cadence across 24 hours).
- Hourly thresholds:
  - Mean/direction: at least 10 observations
  - Sum: at least 12 observations
  - Min/Max: at least 1 observation

## Recommended Update Process

1. Update code in smallest possible scope.
2. Verify both `hourly` and `5min` modes.
3. Verify restricted stations behavior.
4. Verify graph controls for graphable vs non-graphable parameters.
5. Verify mobile-specific behavior:
   - Hourly first column uses time-only label on small screens with date shown in subheader.
   - Hydrological timestamp column uses tighter width on small screens.
   - Header title wraps to two lines with hamburger menu toggle.
6. Update `daily-view.md` if behavior changed.

## Ownership Notes

- Last updated: 2026-05-07
- Update this date whenever Daily View behavior or dependencies change.
