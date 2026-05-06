# Daily View Technical Documentation

Last updated: 2026-05-06

## 1) Purpose

`daily_view.php` renders a single-day station summary page with:

- Parameter statistics panel (daily summary from 5-minute valid values)
- Graph panel (hourly or 5-minute plotted series)
- Data table panel (hourly aggregates or raw 5-minute values)
- CSV export for the active interval view

The page is designed to support meteorological and hydrological station data with quality-flag filtering.

## 2) Runtime Dependencies

### Internal files

- `daily_view.php` -> `daily_view_helpers.php` (`require_once`)
- `daily_view_helpers.php` -> `header.php` (`renderSharedHeader()`)
- `daily_view_helpers.php` -> `footer.php` (`renderSharedFooter()`)
- `daily_view.php` -> `assets/daily_view.css`
- `daily_view.php` -> `assets/daily_view.js`

### External services/libraries

- Station metadata API:
  - `https://services.cema.udel.edu/internal_services/api/station_metadata/DEOS`
- Data API:
  - `https://services.cema.udel.edu/internal_services/api/data/{station}/{startDate}/{endDate}?...`
- Chart.js:
  - `https://cdn.jsdelivr.net/npm/chart.js`
- html2canvas:
  - `https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js`
- Analytics:
  - Google gtag script

## 3) Request Inputs

Accepted query parameters:

- `station`: station code (uppercased in PHP)
- `type`: `meteorological` (default) or `hydrological`
- `date`: selected day (`Y-m-d`)
- `startDate`: fallback alias used when `date` is missing
- `interval`: `hourly` (default) or `5min`
- `panel`: comma-separated panel state (`stats,graph,table`)
- `graph`: comma-separated selected graph parameters
- `download`: when `csv`, triggers CSV download response

Defaults and normalization:

- Date defaults to `2025-07-30` when invalid/missing.
- Interval is constrained to `hourly` or `5min`.
- Panel state is constrained to `stats`, `graph`, `table`.
- Station is validated against metadata station list; invalid station is cleared.

## 4) High-Level Data Flow

1. Parse/validate request inputs.
2. Load station options from metadata API (filtered by type).
3. If station selected, request subdaily data API for selected day + next-day boundary.
4. Iterate data points:
   - Normalize data type config
   - Convert timestamps to Eastern time
   - Filter invalid observations by flag and numeric checks
   - Build rows for table and series for graph
   - Build stats aggregation state/counts for selected day
5. For `hourly` interval:
   - Aggregate per hour using parameter-specific aggregation rules
   - Enforce minimum required observations per aggregation type
6. Build `statsRows`, `graphConfig`, and final table rows.
7. Render HTML for stats panel, graph panel, and table panel.
8. If `download=csv`, stream CSV and exit.

## 5) Core Business Rules

### Validity filtering

Only observations with flags in `['1', '3', '7']` are treated as valid.

### Completeness

- Completeness denominator is fixed at `288` observations/day (24*12).
- Per-parameter completeness is `valid_count/288`.

### Hourly aggregation thresholds

By aggregation type:

- `avg` and `direction`: at least 10 observations in hour
- `sum`: at least 12 observations in hour
- `min` and `max`: at least 1 observation in hour

If the threshold is not met, the hourly result is missing (`-` in table, `null` in graph).

### Graphability rule

A parameter is graphable for the day when it has at least 1 valid observation for the selected day. If all observations are invalid, the graph control is disabled for that parameter.

### Restricted stations

For stations `DAGF`, `DWAR`, `DSND`, `DJCR`, water columns are removed from output:

- `Water Temperature`
- `Depth to Water`

## 6) Important Helper Responsibilities (`daily_view_helpers.php`)

- Environment/TLS:
  - `isLocalDevelopmentRequest()`
  - `configureCurlTlsOptions()`
- Station metadata:
  - `loadStationOptions()`
- Parameter configuration:
  - `getParameterConfig()`
  - `normalizeUnits()`
- Time and bucket logic:
  - `parseEasternDateTime()`
  - `getTimeBucket()`
- Aggregation pipeline:
  - `initializeAggregateState()`
  - `addAggregateValue()`
  - `finalizeAggregateValue()`
  - `finalizeStatsValue()`
  - `getMinimumRequiredObservations()`
- Formatting:
  - `formatConvertedValue()`
  - `getGraphUnit()`
  - `getAggregationLabel()`
  - `degreesToCardinal()`
- Output/render:
  - `downloadCsv()`
  - `renderSharedHeader()`
  - `renderSharedFooter()`

## 7) Frontend Behavior (`assets/daily_view.js`)

Main responsibilities:

- Synchronize form controls and URL params.
- Toggle panel visibility (`stats`, `graph`, `table`) and persist panel state.
- Build and render Chart.js datasets from server-provided `graphData`.
- Maintain graph selections and avoid disabled (non-graphable) parameters.
- Handle date navigation and submission behavior.
- Trigger CSV/interaction analytics events.
- Support graph image download using html2canvas.

## 8) Styling Notes (`assets/daily_view.css`)

- Defines quick-facelift visual system (panels, controls, tables, graph layout).
- Includes panel visibility states and responsive behavior.
- Includes graph-control styles for enabled and disabled parameter chips.

## 9) Error Handling and Failures

Server-side handling:

- API failures (network/cURL/HTTP/non-JSON) are logged to PHP error log.
- User-facing fallback text: unable to load subdaily data.
- Invalid date input is sanitized to default date.

Operational dependencies:

- Page behavior depends on metadata/data API availability.
- Graph rendering depends on Chart.js loading successfully.

## 10) Manual Test Checklist

Run these checks after modifying Daily View:

1. Open page with no station; confirm prompt to select station.
2. Select station/date and verify data loads.
3. Switch interval (`hourly`/`5min`) and confirm table/graph updates.
4. Toggle each panel and refresh URL; confirm state persistence.
5. Verify graph controls:
   - Graphable parameter is selectable.
   - Non-graphable parameter is disabled and cannot be selected.
6. Verify completeness values in stats table are reasonable (`x/288`).
7. Verify restricted station hides water columns.
8. Verify CSV download works and reflects active interval output.
9. Verify previous/next date buttons submit and load expected day.

## 11) Suggested Future Improvements

- Move API key out of source into environment/config.
- Add PHPDoc comments for helper functions and key data structures.
- Add small integration tests for parsing/aggregation and graphability rules.
- Centralize constants (valid flags, thresholds, restricted stations) in one config location.
