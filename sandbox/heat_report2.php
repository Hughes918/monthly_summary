<?php
header('Content-Type: text/html; charset=UTF-8');

$locations = [
    ['label' => 'Wilmington, Delaware', 'lat' => 39.7447, 'lon' => -75.5484],
    ['label' => 'Dover, Delaware', 'lat' => 39.1582, 'lon' => -75.5244],
    ['label' => 'Georgetown, Delaware', 'lat' => 38.6901, 'lon' => -75.3854],
];

$heatRiskLevels = [
    '0' => 'Little to none',
    '1' => 'Minor',
    '2' => 'Moderate',
    '3' => 'Major',
    '4' => 'Extreme',
];

const HEAT_REPORT2_WX_USER_AGENT = 'monthly_summary-heat_report2/1.0 (local dev; no contact)';

$tzEt = new DateTimeZone('America/New_York');

/**
 * @return array{0: string, 1: string}
 */
function heat_report2_http_get(string $url, bool $isLocal, array $extraHeaders = [], ?string $acceptOverride = null): array
{
    $ch = curl_init();
    $acceptHeader = $acceptOverride ?? 'application/geo+json, application/json';
    $headers = array_merge([
        'Accept: ' . $acceptHeader,
        'User-Agent: ' . HEAT_REPORT2_WX_USER_AGENT,
    ], $extraHeaders);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($isLocal) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false || $curlErr !== '') {
        return ['HTTP error: ' . $curlErr, ''];
    }
    if ($httpCode !== 200) {
        return ['HTTP ' . $httpCode, (string) $response];
    }

    return ['', (string) $response];
}

/**
 * CPC 6-10 day state outlook for Delaware from the daily prognostic discussion page.
 *
 * @return array{ok: bool, paragraph: string, valid_display: string, source_url: string}
 */
function heat_report2_cpc_next_week_forecast(bool $isLocal): array
{
    $cpcUrl = 'https://www.cpc.ncep.noaa.gov/products/predictions/610day/fxus06.html';
    [$err, $html] = heat_report2_http_get($cpcUrl, $isLocal, [], 'text/html, application/xhtml+xml, */*;q=0.8');
    if ($err !== '' || $html === '') {
        return [
            'ok' => false,
            'paragraph' => 'The CPC 6-10 day outlook for Delaware could not be loaded automatically. See the daily prognostic discussion at the Climate Prediction Center for the latest state outlooks.',
            'valid_display' => '',
            'source_url' => $cpcUrl,
        ];
    }

    $pos = stripos($html, '6-10 DAY OUTLOOK TABLE');
    if ($pos === false) {
        return [
            'ok' => false,
            'paragraph' => 'The CPC 6-10 day outlook table was not found in the latest discussion page (layout may have changed).',
            'valid_display' => '',
            'source_url' => $cpcUrl,
        ];
    }

    $end = stripos($html, '8-14 DAY OUTLOOK TABLE', $pos);
    $chunk = $end === false ? substr($html, $pos) : substr($html, $pos, $end - $pos);
    $plain = html_entity_decode(strip_tags($chunk), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plain = preg_replace('/\s+/u', ' ', $plain);
    if (!is_string($plain)) {
        $plain = '';
    }

    $validDisplay = '';
    if (preg_match('/Outlook for\s+(.+?)\s+STATE\s+TEMP\s+PCPN/is', $plain, $mv)) {
        $validDisplay = trim($mv[1]);
    }

    if (!preg_match('/DELAWARE\s+([ABN])\s+([ABN])/i', $plain, $md)) {
        return [
            'ok' => false,
            'paragraph' => 'Delaware outlook categories could not be read from the CPC 6-10 day table.',
            'valid_display' => $validDisplay,
            'source_url' => $cpcUrl,
        ];
    }

    $tCat = strtoupper($md[1]);
    $pCat = strtoupper($md[2]);
    $tempPhrases = [
        'A' => 'a tilt toward above-average temperatures',
        'B' => 'a tilt toward below-average temperatures',
        'N' => 'near-normal temperatures',
    ];
    $rainPhrases = [
        'A' => 'above-median rainfall (wetter-than-usual conditions)',
        'B' => 'below-median rainfall (drier-than-usual conditions)',
        'N' => 'near-median rainfall',
    ];
    $tempPhrase = $tempPhrases[$tCat] ?? 'mixed temperature signals';
    $rainPhrase = $rainPhrases[$pCat] ?? 'mixed precipitation signals';

    $validClause = $validDisplay !== '' ? ' (valid ' . $validDisplay . ')' : '';
    $paragraph = 'The CPC\'s 6-10 day outlook' . $validClause
        . ' for Delaware indicates ' . $tempPhrase . ', along with ' . $rainPhrase
        . ', based on the state-level categorical outlook in the CPC daily prognostic discussion.';

    return [
        'ok' => true,
        'paragraph' => $paragraph,
        'valid_display' => $validDisplay,
        'source_url' => $cpcUrl,
    ];
}

/**
 * Rothfusz heat index (°F) when T >= 80; otherwise null (not meaningful for this column).
 *
 * @return float|null
 */
function heat_report2_heat_index_f(float $tempF, ?float $rhPercent)
{
    if ($rhPercent === null || $rhPercent < 0 || $rhPercent > 100) {
        return null;
    }
    if ($tempF < 80) {
        return null;
    }

    $T = $tempF;
    $R = $rhPercent;
    $HI = -42.379
        + 2.04901523 * $T
        + 10.14333127 * $R
        - 0.22475541 * $T * $R
        - 6.83783e-3 * $T * $T
        - 5.481717e-2 * $R * $R
        + 1.22874e-3 * $T * $T * $R
        + 8.5282e-4 * $T * $R * $R
        - 1.99e-6 * $T * $T * $R * $R;

    if ($R <= 13 && $T >= 80 && $T <= 112) {
        $HI -= ((13 - $R) / 4) * sqrt((17 - abs($T - 95)) / 17);
    } elseif ($R > 85 && $T >= 80 && $T <= 87) {
        $HI += 0.02 * ($R - 85) * (87 - $T);
    }

    return $HI;
}

/**
 * @return array{nws_city: string, nws_state: string, grid_id: string, grid_x: int, grid_y: int, forecast_url: string, forecast_hourly_url: string}
 */
function heat_report2_points_meta(array $points): array
{
    $props = $points['properties'] ?? [];
    $rel = is_array($props['relativeLocation'] ?? null)
        ? ($props['relativeLocation']['properties'] ?? [])
        : [];

    return [
        'nws_city' => trim((string) ($rel['city'] ?? '')),
        'nws_state' => trim((string) ($rel['state'] ?? '')),
        'grid_id' => trim((string) ($props['gridId'] ?? '')),
        'grid_x' => (int) ($props['gridX'] ?? 0),
        'grid_y' => (int) ($props['gridY'] ?? 0),
        'forecast_url' => trim((string) ($props['forecast'] ?? '')),
        'forecast_hourly_url' => trim((string) ($props['forecastHourly'] ?? '')),
    ];
}

/**
 * @return array{0: string, 1: list<array<string, mixed>>}
 */
function heat_report2_forecast_periods(string $forecastUrl, bool $isLocal): array
{
    if ($forecastUrl === '') {
        return ['Weather.gov forecast URL missing.', []];
    }

    [$err, $body] = heat_report2_http_get($forecastUrl, $isLocal);
    if ($err !== '') {
        return ['Weather.gov forecast: ' . $err, []];
    }
    $forecast = json_decode($body, true);
    if (!is_array($forecast)) {
        return ['Weather.gov forecast: invalid JSON.', []];
    }
    $periods = $forecast['properties']['periods'] ?? [];
    if (!is_array($periods)) {
        return ['Weather.gov forecast: no periods.', []];
    }

    return ['', $periods];
}

/**
 * @param list<array<string, mixed>> $periods
 */
function heat_report2_period_pop_percent(array $period): ?int
{
    $pop = $period['probabilityOfPrecipitation'] ?? null;
    if (!is_array($pop) || !array_key_exists('value', $pop) || $pop['value'] === null) {
        return null;
    }
    if (!is_numeric($pop['value'])) {
        return null;
    }

    return (int) round((float) $pop['value']);
}

/**
 * @param list<array<string, mixed>> $periods
 *
 * @return array<string, array{max_t: ?float, min_t: ?float}>
 */
function heat_report2_daily_stats_from_forecast_periods(array $periods, DateTimeZone $tzEt): array
{
    /** @var array<string, array{max_t: ?float, min_t: ?float}> $byDate */
    $byDate = [];

    foreach ($periods as $p) {
        if (!is_array($p)) {
            continue;
        }
        $start = isset($p['startTime']) ? (string) $p['startTime'] : '';
        if ($start === '' || !isset($p['temperature']) || !is_numeric($p['temperature'])) {
            continue;
        }
        try {
            $dateKey = (new DateTimeImmutable($start))->setTimezone($tzEt)->format('Y-m-d');
        } catch (Exception $e) {
            continue;
        }

        if (!isset($byDate[$dateKey])) {
            $byDate[$dateKey] = ['max_t' => null, 'min_t' => null];
        }

        $temp = (float) $p['temperature'];
        if (!empty($p['isDaytime'])) {
            $byDate[$dateKey]['max_t'] = $temp;
        } else {
            $byDate[$dateKey]['min_t'] = $temp;
        }
    }

    return $byDate;
}

/**
 * @param list<array<string, mixed>> $hourlyPeriods
 *
 * @return array<string, ?float>
 */
function heat_report2_daytime_max_heat_index_by_date(array $hourlyPeriods, DateTimeZone $tzEt): array
{
    /** @var array<string, list<float>> $hisByDate */
    $hisByDate = [];

    foreach ($hourlyPeriods as $p) {
        if (!is_array($p)) {
            continue;
        }
        if (array_key_exists('isDaytime', $p) && empty($p['isDaytime'])) {
            continue;
        }
        $start = isset($p['startTime']) ? (string) $p['startTime'] : '';
        if ($start === '' || !isset($p['temperature'])) {
            continue;
        }
        try {
            $dateKey = (new DateTimeImmutable($start))->setTimezone($tzEt)->format('Y-m-d');
        } catch (Exception $e) {
            continue;
        }

        $t = (float) $p['temperature'];
        $rh = null;
        if (isset($p['relativeHumidity']) && is_array($p['relativeHumidity']) && array_key_exists('value', $p['relativeHumidity'])) {
            $v = $p['relativeHumidity']['value'];
            if ($v !== null && is_numeric($v)) {
                $rh = (float) $v;
            }
        }
        $hi = heat_report2_heat_index_f($t, $rh);
        if ($hi !== null) {
            $hisByDate[$dateKey][] = $hi;
        }
    }

    $out = [];
    foreach ($hisByDate as $key => $his) {
        $out[$key] = max($his);
    }

    return $out;
}

/**
 * @return array{0: string, 1: list<array<string, mixed>>, 2: array{nws_city: string, nws_state: string, grid_id: string, grid_x: int, grid_y: int, forecast_url: string, forecast_hourly_url: string}}
 */
function heat_report2_hourly_periods_from_points(float $lat, float $lon, bool $isLocal): array
{
    $emptyMeta = [
        'nws_city' => '',
        'nws_state' => '',
        'grid_id' => '',
        'grid_x' => 0,
        'grid_y' => 0,
        'forecast_url' => '',
        'forecast_hourly_url' => '',
    ];

    $pointsUrl = 'https://api.weather.gov/points/' . round($lat, 4) . ',' . round($lon, 4);

    [$err, $body] = heat_report2_http_get($pointsUrl, $isLocal);
    if ($err !== '') {
        return ['Weather.gov points: ' . $err, [], $emptyMeta];
    }
    $points = json_decode($body, true);
    if (!is_array($points)) {
        return ['Weather.gov points: invalid JSON.', [], $emptyMeta];
    }

    $meta = heat_report2_points_meta($points);
    $hourlyUrl = $meta['forecast_hourly_url'];
    if ($hourlyUrl === '') {
        return ['Weather.gov points: missing forecastHourly URL.', [], $meta];
    }

    [$err2, $hBody] = heat_report2_http_get($hourlyUrl, $isLocal);
    if ($err2 !== '') {
        return ['Weather.gov hourly: ' . $err2, [], $meta];
    }
    $hourly = json_decode($hBody, true);
    if (!is_array($hourly)) {
        return ['Weather.gov hourly: invalid JSON.', [], $meta];
    }
    $periods = $hourly['properties']['periods'] ?? [];
    if (!is_array($periods)) {
        return ['Weather.gov hourly: no periods.', [], $meta];
    }

    return ['', $periods, $meta];
}

/**
 * @param list<array{forecast_periods?: list<array<string, mixed>>}> $siteResults
 */
function heat_report2_statewide_precip_bullet(array $siteResults, DateTimeZone $tzEt): string
{
    $maxPop = -1;
    $maxPopLabel = '';
    $daysWithChance = [];

    foreach ($siteResults as $block) {
        foreach ($block['forecast_periods'] ?? [] as $p) {
            if (!is_array($p)) {
                continue;
            }
            $pop = heat_report2_period_pop_percent($p);
            if ($pop === null) {
                continue;
            }
            if ($pop > $maxPop) {
                $maxPop = $pop;
                $maxPopLabel = trim((string) ($p['name'] ?? ''));
            }
            if ($pop < 20) {
                continue;
            }
            $start = isset($p['startTime']) ? (string) $p['startTime'] : '';
            if ($start === '') {
                continue;
            }
            try {
                $daysWithChance[(new DateTimeImmutable($start))->setTimezone($tzEt)->format('Y-m-d')] = true;
            } catch (Exception $e) {
                continue;
            }
        }
    }

    if ($maxPop < 0) {
        return 'Precipitation chances from NWS point forecasts are unavailable for the three sample cities.';
    }

    $dayCount = count($daysWithChance);
    if ($maxPop < 15 && $dayCount === 0) {
        return 'Precipitation chances stay low statewide through the period (generally under 15% at Wilmington, Dover, and Georgetown).';
    }

    if ($maxPop < 30 && $dayCount <= 1) {
        $tail = $maxPopLabel !== '' ? ' (peak near ' . $maxPop . '% on ' . $maxPopLabel . ')' : '';
        return 'Mostly dry statewide, with only isolated NWS periods showing a slight chance of rain' . $tail . '.';
    }

    $parts = ['Statewide, NWS forecasts show'];
    if ($dayCount > 0) {
        $parts[] = 'at least a 20% chance of precipitation on '
            . $dayCount . ' day' . ($dayCount === 1 ? '' : 's') . ' somewhere in the sample cities';
    }
    if ($maxPop >= 30) {
        $peak = 'highest odds near ' . $maxPop . '%';
        if ($maxPopLabel !== '') {
            $peak .= ' (' . $maxPopLabel . ')';
        }
        $parts[] = $peak;
    }

    return implode(', ', $parts) . '.';
}

/**
 * @param array<string, string> $heatRiskLevels
 * @param list<array{label: string, rows?: list<array<string, mixed>>}> $siteResults
 */
function heat_report2_heat_risk_overview_bullet(array $siteResults, array $heatRiskLevels): string
{
    $cityPeaks = [];
    $statePeak = 0;

    foreach ($siteResults as $block) {
        $short = preg_replace('/, Delaware$/i', '', $block['label']);
        if (!is_string($short) || $short === '') {
            $short = $block['label'];
        }
        $peak = 0;
        foreach ($block['rows'] ?? [] as $r) {
            if (isset($r['value']) && is_numeric($r['value'])) {
                $peak = max($peak, (int) $r['value']);
            }
        }
        $cityPeaks[$short] = $peak;
        $statePeak = max($statePeak, $peak);
    }

    if ($cityPeaks === []) {
        return 'Heat risk data is not available yet for the Delaware sample cities.';
    }

    $peakLabel = $heatRiskLevels[(string) $statePeak] ?? ('level ' . $statePeak);
    $cityParts = [];
    foreach ($cityPeaks as $city => $lvl) {
        $cityParts[] = $city . ' peaks at ' . ($heatRiskLevels[(string) $lvl] ?? ('level ' . $lvl));
    }

    return 'Heat risk reaches ' . $peakLabel . ' (' . $statePeak . ') statewide; '
        . implode(', ', $cityParts) . '.';
}

/**
 * @param list<array{rows?: list<array<string, mixed>>}> $siteResults
 */
function heat_report2_high_temp_overview_bullet(array $siteResults): string
{
    $highs = [];
    foreach ($siteResults as $block) {
        foreach ($block['rows'] ?? [] as $r) {
            if (isset($r['wx_max_f']) && $r['wx_max_f'] !== null && is_numeric($r['wx_max_f'])) {
                $highs[] = (float) $r['wx_max_f'];
            }
        }
    }

    if ($highs === []) {
        return 'Forecast high temperatures are not available yet for the sample cities.';
    }

    $lo = (int) round(min($highs));
    $hi = (int) round(max($highs));

    if ($hi - $lo <= 4) {
        return 'NWS forecast daily highs are mostly around ' . $lo . ' to ' . $hi . ' °F across Wilmington, Dover, and Georgetown.';
    }

    return 'NWS forecast daily highs range from about ' . $lo . ' to ' . $hi . ' °F across Wilmington, Dover, and Georgetown.';
}

/**
 * @return array{0: string, 1: list<array{forecast_day: ?int, valid_ms: int, value: string, label: string}>}
 */
function heat_report2_build_result(string $identifyUrl, array $heatRiskLevels, bool $isLocal): array
{
    [$err, $response] = heat_report2_http_get($identifyUrl, $isLocal);
    if ($err !== '') {
        return ['Heat risk service: ' . $err, []];
    }

    $payload = json_decode($response, true);
    if (!is_array($payload)) {
        return ['Invalid JSON response.', []];
    }

    $values = $payload['properties']['Values'] ?? [];
    $features = $payload['catalogItems']['features'] ?? [];
    if (!is_array($values) || !is_array($features)) {
        return ['Response missing expected fields.', []];
    }

    // Values[i] is the risk at the point for catalogItems.features[i] (same array index).
    $heatRows = [];
    $n = min(count($values), count($features));
    for ($i = 0; $i < $n; $i++) {
        $attrs = $features[$i]['attributes'] ?? [];
        if (!is_array($attrs)) {
            continue;
        }
        $name = (string) ($attrs['name'] ?? '');
        $validMs = isset($attrs['idp_validtime']) ? (int) $attrs['idp_validtime'] : 0;
        $rawValue = trim((string) ($values[$i] ?? ''));
        $forecastDay = null;
        if (preg_match('/HeatRisk_(\d+)_/i', $name, $m)) {
            $forecastDay = (int) $m[1];
        }
        $heatRows[] = [
            'forecast_day' => $forecastDay,
            'valid_ms' => $validMs,
            'value' => $rawValue,
            'label' => $heatRiskLevels[$rawValue] ?? ('Level ' . $rawValue),
        ];
    }

    usort($heatRows, static function ($a, $b) {
        return $a['valid_ms'] <=> $b['valid_ms'];
    });

    return ['', $heatRows];
}

function heat_report2_identify_url(float $lat, float $lon): string
{
    $base = 'https://mapservices.weather.noaa.gov/experimental/rest/services/NWS_HeatRisk/ImageServer/identify';

    return $base . '?' . http_build_query([
        'f' => 'pjson',
        'geometry' => json_encode([
            'x' => $lon,
            'y' => $lat,
            'spatialReference' => ['wkid' => 4326],
        ]),
        'geometryType' => 'esriGeometryPoint',
        'inSR' => 4326,
        'returnGeometry' => 'false',
        'returnCatalogItems' => 'true',
        'returnAllPixelValues' => 'true',
    ], '', '&', PHP_QUERY_RFC3986);
}

/**
 * @param list<array{rows?: list<array<string, mixed>>}> $siteResults
 *
 * @return array{issued: string, period: string, bullets: list<string>}
 */
function heat_report2_build_report_overview(array $siteResults, array $heatRiskLevels, DateTimeZone $tzEt): array
{
    $issued = (new DateTimeImmutable('now', $tzEt))->format('l, F j, Y');

    $allValidMs = [];
    foreach ($siteResults as $block) {
        foreach ($block['rows'] ?? [] as $r) {
            if (!empty($r['valid_ms'])) {
                $allValidMs[] = (int) $r['valid_ms'];
            }
        }
    }

    $enDash = "\u{2013}";
    if ($allValidMs === []) {
        return [
            'issued' => $issued,
            'period' => 'Unavailable (no valid forecast dates yet).',
            'bullets' => [
                'Heat risk scores are not available yet.',
                'Forecast high temperatures are not available yet.',
                'Precipitation chances are not available yet.',
            ],
        ];
    }

    $minMs = min($allValidMs);
    $maxMs = max($allValidMs);
    $d0 = (new DateTimeImmutable('@' . (int) floor($minMs / 1000)))->setTimezone($tzEt);
    $d1 = (new DateTimeImmutable('@' . (int) floor($maxMs / 1000)))->setTimezone($tzEt);
    $period = $d0->format('l, F j') . $enDash . $d1->format('l, F j, Y');

    return [
        'issued' => $issued,
        'period' => $period,
        'bullets' => [
            heat_report2_heat_risk_overview_bullet($siteResults, $heatRiskLevels),
            heat_report2_high_temp_overview_bullet($siteResults),
            heat_report2_statewide_precip_bullet($siteResults, $tzEt),
        ],
    ];
}

$serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
$httpHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocal = in_array(trim(explode(':', $serverName)[0]), ['localhost', '127.0.0.1', '::1'], true)
    || in_array(trim(explode(':', $httpHost)[0]), ['localhost', '127.0.0.1', '::1'], true);

$siteResults = [];
foreach ($locations as $loc) {
    $lat = (float) $loc['lat'];
    $lon = (float) $loc['lon'];
    $url = heat_report2_identify_url($lat, $lon);
    [$err, $rows] = heat_report2_build_result($url, $heatRiskLevels, $isLocal);

    [$wxErr, $hourlyPeriods, $nwsMeta] = heat_report2_hourly_periods_from_points($lat, $lon, $isLocal);

    $forecastPeriods = [];
    $forecastErr = '';
    if ($nwsMeta['forecast_url'] !== '') {
        [$forecastErr, $forecastPeriods] = heat_report2_forecast_periods($nwsMeta['forecast_url'], $isLocal);
    }

    $dailyWx = heat_report2_daily_stats_from_forecast_periods($forecastPeriods, $tzEt);
    $maxHiByDate = heat_report2_daytime_max_heat_index_by_date($hourlyPeriods, $tzEt);

    $mergedRows = [];
    foreach ($rows as $row) {
        $dateKey = '';
        if ($row['valid_ms'] > 0) {
            $d = (new DateTimeImmutable('@' . (int) floor($row['valid_ms'] / 1000)))->setTimezone($tzEt);
            $dateKey = $d->format('Y-m-d');
        }
        $wx = ($dateKey !== '' && isset($dailyWx[$dateKey])) ? $dailyWx[$dateKey] : null;
        $mergedRows[] = array_merge($row, [
            'date_key' => $dateKey,
            'wx_max_f' => $wx['max_t'] ?? null,
            'wx_min_f' => $wx['min_t'] ?? null,
            'wx_max_hi_f' => $maxHiByDate[$dateKey] ?? null,
        ]);
    }

    $pointsUrl = 'https://api.weather.gov/points/' . round($lat, 4) . ',' . round($lon, 4);

    $siteResults[] = [
        'label' => $loc['label'],
        'lat' => $lat,
        'lon' => $lon,
        'url' => $url,
        'error' => $err,
        'rows' => $mergedRows,
        'weather_error' => $wxErr !== '' ? $wxErr : $forecastErr,
        'forecast_periods' => $forecastPeriods,
        'points_url' => $pointsUrl,
    ];
}

$fmtTempCell = static function ($v): string {
    if ($v === null) {
        return '—';
    }

    return (string) (int) round((float) $v);
};

$reportOverview = heat_report2_build_report_overview($siteResults, $heatRiskLevels, $tzEt);
$cpcNextWeek = heat_report2_cpc_next_week_forecast($isLocal);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delaware Heat Report</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 1rem; line-height: 1.45; max-width: 56rem; }
        .report-header { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #ccc; }
        .report-title { font-size: 1.35rem; margin: 0 0 0.65rem; font-weight: 700; }
        .report-meta { margin: 0.2rem 0; }
        .report-summary-heading { font-size: 1.05rem; margin: 1rem 0 0.4rem; }
        .report-summary { margin: 0.35rem 0 0; padding-left: 1.35rem; }
        .report-summary li { margin-bottom: 0.35rem; }
        .next-week-forecast { margin-top: 2.5rem; padding-top: 1rem; border-top: 1px solid #ccc; }
        .next-week-forecast h2 { font-size: 1.15rem; margin: 0 0 0.5rem; }
        .next-week-forecast p { margin: 0 0 0.35rem; max-width: 52rem; }
        .next-week-forecast .cpc-link { font-size: 0.88rem; margin-top: 0.5rem; }
        .next-week-forecast .heat-risk-cat-figure { margin: 1rem 0 0; max-width: 52rem; }
        .next-week-forecast .heat-risk-cat-img { display: block; max-width: 100%; height: auto; }
        .city-block { margin-bottom: 2.25rem; }
        .city-block:first-child h2 { margin-top: 0; }
        h2 { font-size: 1.15rem; margin: 0 0 0.5rem; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 0.5rem 0.65rem; text-align: left; }
        th { background: #f2f2f2; }
        th.forecast-day-col,
        td.forecast-day-cell {
            min-width: 17rem;
            width: 26%;
            white-space: nowrap;
            font-size: 0.95rem;
        }
        tr.heat-risk-1 td { background: #fff9c4; color: #222; }
        tr.heat-risk-2 td { background: #ffe0b2; color: #222; }
        tr.heat-risk-3 td { background: #ffcdd2; color: #1a1a1a; }
        tr.heat-risk-4 td { background: #e1bee7; color: #1a1a1a; }
        .err { color: #a00; }
        .muted { color: #555; }
        .page-notes { margin-top: 3rem; padding-top: 1.25rem; border-top: 1px solid #ccc; font-size: 0.88rem; color: #333; }
        .page-notes h2 { font-size: 1rem; margin: 0 0 0.5rem; }
        .page-notes h3 { font-size: 0.95rem; margin: 1rem 0 0.35rem; }
        .page-notes ul { margin: 0.25rem 0 0; padding-left: 1.2rem; }
        .page-notes li { margin-bottom: 0.75rem; }
        .page-notes a { word-break: break-all; }
    </style>
</head>
<body>
    <header class="report-header">
        <h1 class="report-title">Delaware Heat Report</h1>
        <p class="report-meta"><strong>Issued:</strong> <?php echo htmlspecialchars($reportOverview['issued']); ?></p>
        <p class="report-meta"><strong>Period:</strong> <?php echo htmlspecialchars($reportOverview['period']); ?></p>
        <h2 class="report-summary-heading">Summary</h2>
        <ul class="report-summary">
            <?php foreach ($reportOverview['bullets'] as $bullet): ?>
                <li><?php echo htmlspecialchars($bullet); ?></li>
            <?php endforeach; ?>
        </ul>
    </header>

    <?php foreach ($siteResults as $block): ?>
        <section class="city-block">
            <h2><?php echo htmlspecialchars($block['label']); ?></h2>
            <table>
                <thead>
                    <tr>
                        <th scope="col" class="forecast-day-col">Forecast Day</th>
                        <th scope="col">Daily max °F</th>
                        <th scope="col">Max heat index °F</th>
                        <th scope="col">Daily min °F</th>
                        <th scope="col">Heat risk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($block['weather_error'] !== ''): ?>
                        <tr>
                            <td colspan="5" class="err"><?php echo htmlspecialchars($block['weather_error']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($block['error'] !== ''): ?>
                        <tr>
                            <td colspan="5" class="err"><?php echo htmlspecialchars($block['error']); ?></td>
                        </tr>
                    <?php elseif (empty($block['rows'])): ?>
                        <tr>
                            <td colspan="5" class="muted">No data.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($block['rows'] as $row): ?>
                            <?php
                            $validEt = '';
                            if ($row['valid_ms'] > 0) {
                                $dt = (new DateTimeImmutable('@' . (int) floor($row['valid_ms'] / 1000)))->setTimezone($tzEt);
                                $validEt = $dt->format('l, M j, Y');
                            }
                            $risk = isset($row['value']) && is_numeric($row['value']) ? (int) $row['value'] : -1;
                            $risk = ($risk >= 0 && $risk <= 4) ? $risk : 0;
                            $rowClass = ($risk >= 1 && $risk <= 4) ? 'heat-risk-' . $risk : '';
                            ?>
                            <tr<?php echo $rowClass !== '' ? ' class="' . htmlspecialchars($rowClass) . '"' : ''; ?>>
                                <td class="forecast-day-cell"><?php echo htmlspecialchars($validEt !== '' ? $validEt : '—'); ?></td>
                                <td><?php echo htmlspecialchars($fmtTempCell($row['wx_max_f'] ?? null)); ?></td>
                                <td><?php echo htmlspecialchars($fmtTempCell($row['wx_max_hi_f'] ?? null)); ?></td>
                                <td><?php echo htmlspecialchars($fmtTempCell($row['wx_min_f'] ?? null)); ?></td>
                                <td><?php echo htmlspecialchars($row['label']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php endforeach; ?>

    <section class="next-week-forecast" aria-labelledby="next-week-heading">
        <h2 id="next-week-heading">Next Week Forecast</h2>
        <p><?php echo htmlspecialchars($cpcNextWeek['paragraph']); ?></p>
        <p class="cpc-link">
            <a href="<?php echo htmlspecialchars($cpcNextWeek['source_url']); ?>">CPC 6-10 day discussion &amp; outlook tables</a>
            (Climate Prediction Center).
        </p>
        <?php
        $heatRiskCatFile = __DIR__ . '/heat_risk_cat.png';
        $heatRiskCatVer = is_file($heatRiskCatFile) ? (int) filemtime($heatRiskCatFile) : time();
        $heatRiskCatSize = is_file($heatRiskCatFile) ? @getimagesize($heatRiskCatFile) : false;
        $heatRiskCatW = is_array($heatRiskCatSize) && isset($heatRiskCatSize[0]) ? (int) $heatRiskCatSize[0] : null;
        $heatRiskCatH = is_array($heatRiskCatSize) && isset($heatRiskCatSize[1]) ? (int) $heatRiskCatSize[1] : null;
        ?>
        <figure class="heat-risk-cat-figure">
            <img
                class="heat-risk-cat-img"
                src="heat_risk_cat.png?v=<?php echo $heatRiskCatVer; ?>"
                <?php if ($heatRiskCatW !== null && $heatRiskCatH !== null): ?>width="<?php echo $heatRiskCatW; ?>" height="<?php echo $heatRiskCatH; ?>" <?php endif; ?>
                alt="NWS heat risk category reference: levels 0 (little to none) through 4 (extreme) with color scale."
                loading="lazy"
                decoding="async"
            >
        </figure>
    </section>

    <footer class="page-notes">
        <h2>About this page</h2>
        <p>
            Seven-day heat risk from the NOAA experimental ImageServer identify service
            (<code>Values[i]</code> paired with <code>catalogItems.features[i]</code>). Daily high and low from NWS
            <code>/forecast</code> day/night periods; max heat index from daytime <code>forecastHourly</code> hours.
            Rows are sorted by heat-risk valid date in Eastern Time.
        </p>

        <h3>Max heat index column</h3>
        <p>
            Values use the NWS Rothfusz regression on hourly temperature and relative humidity when the temperature is at least 80&nbsp;°F.
            When no hour that day reaches 80&nbsp;°F, the cell shows an em dash.
        </p>

        <h3>Sample coordinates (WGS84)</h3>
        <p>Each city uses a fixed downtown-style latitude and longitude for both services.</p>

        <h3>Data endpoints</h3>
        <ul>
            <?php foreach ($siteResults as $block): ?>
                <li>
                    <strong><?php echo htmlspecialchars($block['label']); ?></strong>
                    (<?php echo htmlspecialchars(number_format($block['lat'], 4) . '°N, ' . number_format(abs($block['lon']), 4) . '°W'); ?>)
                    <br>
                    Heat risk: <a href="<?php echo htmlspecialchars($block['url']); ?>"><?php echo htmlspecialchars($block['url']); ?></a>
                    <br>
                    Weather.gov points: <a href="<?php echo htmlspecialchars($block['points_url']); ?>"><?php echo htmlspecialchars($block['points_url']); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <h3>api.weather.gov requests</h3>
        <p>
            Calls send <code>Accept: application/geo+json, application/json</code> and a descriptive <code>User-Agent</code>
            (<code><?php echo htmlspecialchars(HEAT_REPORT2_WX_USER_AGENT); ?></code>). Replace that string with your own application id and contact as required by NWS.
        </p>
    </footer>
</body>
</html>
