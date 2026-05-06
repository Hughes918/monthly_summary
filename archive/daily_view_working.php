<?php ob_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <script async src='https://www.googletagmanager.com/gtag/js?id=G-BTMTESR1DZ'></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-BTMTESR1DZ');
    </script>
    <style>
        /* Quick facelift: remove the quick-facelift body class to revert. */
        body.quick-facelift {
            --page-bg: linear-gradient(180deg, #f4f7f1 0%, #eaf1e5 100%);
            --panel-bg: rgba(255, 255, 255, 0.92);
            --panel-border: #d7e2d0;
            --text-main: #183028;
            --text-muted: #5f6f69;
            --accent: #2f6b50;
            --accent-strong: #214f3c;
            --accent-soft: #eef5f0;
            --shadow-soft: 0 14px 34px rgba(21, 44, 33, 0.08);
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
            margin: 0;
            padding: 18px;
            color: var(--text-main);
            background: var(--page-bg);
        }
        .quick-facelift h1 {
            max-width: 1280px;
            margin: 0 auto 18px;
            padding: 8px 4px 0;
            text-align: left;
            font-size: clamp(1.7rem, 2vw + 1rem, 2.5rem);
            line-height: 1.12;
            letter-spacing: -0.03em;
            color: var(--accent-strong);
        }
        .quick-facelift .page-title-wrap {
            max-width: 1280px;
            margin: 0 auto 18px;
            padding: 8px 4px 0;
        }
        .quick-facelift .page-title-wrap h1 {
            margin: 0;
            max-width: none;
            padding: 0;
        }
        .quick-facelift .page-subtitle {
            margin: 8px 0 0;
            font-size: 0.98rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.01em;
        }
        .quick-facelift .controls,
        .quick-facelift .graph-panel,
        .quick-facelift .stats-panel,
        .quick-facelift .table-wrap,
        .quick-facelift > p {
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
        }
        .quick-facelift .controls {
            margin-bottom: 20px;
            padding: 18px;
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 18px;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(8px);
        }
        .quick-facelift .controls form {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 12px;
        }
        .quick-facelift .controls input[type="date"],
        .quick-facelift .controls select {
            min-height: 44px;
            padding: 10px 12px;
            margin: 0;
            font-size: 14px;
            color: var(--text-main);
            background: #fff;
            border: 1px solid #bfd0c3;
            border-radius: 12px;
            box-sizing: border-box;
        }
        .quick-facelift .controls input[type="date"]:focus,
        .quick-facelift .controls select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(47, 107, 80, 0.14);
        }
        .quick-facelift .controls input[type="radio"] {
            margin: 0;
            accent-color: var(--accent);
        }
        .quick-facelift .controls button {
            min-height: 44px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--accent) 0%, #3d8664 100%);
            border: none;
            border-radius: 999px;
            box-shadow: 0 8px 18px rgba(47, 107, 80, 0.18);
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }
        .quick-facelift .controls button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 20px rgba(47, 107, 80, 0.22);
            filter: saturate(1.05);
        }
        .quick-facelift .controls button[aria-expanded="true"] {
            background: linear-gradient(135deg, var(--accent-strong) 0%, var(--accent) 100%);
            box-shadow: 0 12px 22px rgba(33, 79, 60, 0.24);
        }
        .quick-facelift .controls button:focus-visible {
            outline: 3px solid rgba(47, 107, 80, 0.18);
            outline-offset: 2px;
        }
        .quick-facelift .submit-button { margin-left: 0; }
        .quick-facelift .date-group,
        .quick-facelift .radio-group {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-right: 0;
        }
        .quick-facelift .date-group {
            flex-wrap: wrap;
        }
        .quick-facelift .date-group label,
        .quick-facelift .radio-group label {
            margin-right: 0;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }
        .quick-facelift .radio-group {
            padding: 6px;
            border-radius: 14px;
            background: var(--accent-soft);
        }
        .quick-facelift .radio-group > span {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-right: 2px;
        }
        .quick-facelift .radio-group label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.72);
            color: var(--text-main);
            font-size: 14px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: normal;
        }
        .quick-facelift .controls-note {
            flex-basis: 100%;
            margin-top: 2px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .quick-facelift .page-loading {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(244, 247, 241, 0.82);
            backdrop-filter: blur(4px);
            z-index: 999;
        }
        .quick-facelift.is-loading .page-loading {
            display: flex;
        }
        .quick-facelift.is-loading {
            cursor: progress;
        }
        .quick-facelift .loading-card {
            display: grid;
            gap: 12px;
            justify-items: center;
            width: min(100%, 320px);
            padding: 22px 24px;
            text-align: center;
            border: 1px solid var(--panel-border);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: var(--shadow-soft);
            color: var(--text-main);
        }
        .quick-facelift .loading-spinner {
            width: 42px;
            height: 42px;
            border: 4px solid rgba(47, 107, 80, 0.18);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: quick-facelift-spin 0.85s linear infinite;
        }
        .quick-facelift .loading-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--accent-strong);
        }
        .quick-facelift .loading-text {
            font-size: 0.92rem;
            color: var(--text-muted);
        }
        @keyframes quick-facelift-spin {
            to {
                transform: rotate(360deg);
            }
        }
        .quick-facelift .table-wrap {
            overflow-x: auto;
            max-width: 1280px;
            margin-bottom: 20px;
            border: 1px solid var(--panel-border);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow-soft);
        }
        .quick-facelift .data-table,
        .quick-facelift .stats-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .quick-facelift .data-table { min-width: 980px; }
        .quick-facelift .stats-table { min-width: 620px; }
        .quick-facelift th,
        .quick-facelift td {
            border: 0;
            border-bottom: 1px solid #e3ebe3;
            padding: 11px 12px;
            text-align: center;
        }
        .quick-facelift td { white-space: nowrap; }
        .quick-facelift th {
            background: linear-gradient(180deg, #3a7b5b 0%, #2f6b50 100%);
            color: #fff;
        }
        .quick-facelift thead th {
            white-space: normal;
            line-height: 1.2;
            vertical-align: bottom;
        }
        .quick-facelift thead tr:first-child th {
            padding-top: 14px;
            padding-bottom: 5px;
        }
        .quick-facelift thead tr:last-child th {
            padding-top: 5px;
            font-size: 13px;
            font-weight: 500;
        }
        .quick-facelift .agg-row th {
            background: #e4efe7;
            color: #436255;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .quick-facelift .graph-panel,
        .quick-facelift .stats-panel {
            display: none;
            margin-bottom: 18px;
            padding: 18px;
            border: 1px solid var(--panel-border);
            border-radius: 18px;
            background: var(--panel-bg);
            box-shadow: var(--shadow-soft);
        }
        .quick-facelift .graph-panel.is-visible,
        .quick-facelift .stats-panel.is-visible {
            display: block;
        }
        .quick-facelift .graph-panel h2,
        .quick-facelift .stats-panel h2 {
            margin: 0 0 12px;
            font-size: 21px;
            color: var(--accent-strong);
        }
        .quick-facelift .graph-panel p { margin: 0 0 12px; color: var(--text-muted); }
        .quick-facelift .graph-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }
        .quick-facelift .graph-controls label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: 1px solid #d6e1d6;
            border-radius: 999px;
            background: #f8fbf8;
            color: var(--text-main);
        }
        .quick-facelift .graph-controls input[type="checkbox"] { margin: 0; accent-color: var(--accent); }
        .quick-facelift .graph-canvas-wrap { position: relative; min-height: 505px; }
        .quick-facelift .graph-empty {
            padding: 24px;
            color: var(--text-muted);
            text-align: center;
            border: 1px dashed #bfd0c3;
            border-radius: 14px;
            background: #fafdfb;
        }
        .quick-facelift tbody tr:nth-child(even) { background-color: #f8fbf8; }
        .quick-facelift tbody tr:hover { background-color: #eef5f0; }
        .quick-facelift .timestamp {
            font-weight: 700;
            color: var(--accent-strong);
            background: #fbfdfb;
            position: sticky;
            left: 0;
            z-index: 1;
        }
        .quick-facelift thead .timestamp,
        .quick-facelift thead th:first-child {
            position: sticky;
            left: 0;
            z-index: 2;
        }
        @media (max-width: 900px) {
            body.quick-facelift {
                padding: 12px;
            }
            .quick-facelift .controls form {
                gap: 10px;
            }
            .quick-facelift .date-group,
            .quick-facelift .radio-group {
                width: 100%;
                justify-content: flex-start;
            }
            .quick-facelift .date-group {
                align-items: stretch;
            }
            .quick-facelift .date-group input[type="date"],
            .quick-facelift .date-group select,
            .quick-facelift .date-group button {
                width: 100%;
            }
            .quick-facelift .controls button[type="submit"][name="download"],
            .quick-facelift .controls button[type="button"] {
                flex: 1 1 140px;
            }
        }
        @media (max-width: 640px) {
            .quick-facelift h1 {
                margin-bottom: 14px;
                font-size: 1.5rem;
            }
            .quick-facelift .controls,
            .quick-facelift .graph-panel,
            .quick-facelift .stats-panel {
                padding: 14px;
                border-radius: 16px;
            }
            .quick-facelift .controls form {
                flex-direction: column;
                align-items: stretch;
            }
            .quick-facelift .date-group,
            .quick-facelift .radio-group {
                width: 100%;
            }
            .quick-facelift .radio-group {
                flex-wrap: wrap;
            }
            .quick-facelift .radio-group label {
                flex: 1 1 140px;
                justify-content: center;
            }
            .quick-facelift .controls button {
                width: 100%;
            }
            .quick-facelift .graph-canvas-wrap {
                min-height: 340px;
            }
            .quick-facelift th,
            .quick-facelift td {
                padding: 10px;
            }
        }
    </style>
</head>
<body class='quick-facelift'>

<div class='page-loading' id='pageLoading' aria-hidden='true'>
    <div class='loading-card' role='status' aria-live='polite'>
        <div class='loading-spinner' aria-hidden='true'></div>
        <div class='loading-title'>Loading updated data</div>
        <div class='loading-text'>Fetching the selected station and date. This can take a moment.</div>
    </div>
</div>

<?php

$apiKey = 'C7384045980DCA07FD2036B439448C1A281132329DE17A8CD8F03810091AF107';

// Get dates from form or use defaults
$station = isset($_GET['station']) && trim($_GET['station']) !== '' ? strtoupper(trim($_GET['station'])) : 'DAGF';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : (isset($_GET['startDate']) ? $_GET['startDate'] : '2025-07-30');
$startDate = $selectedDate;
$endDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
$downloadFormat = isset($_GET['download']) ? $_GET['download'] : '';
$requestedInterval = isset($_GET['interval']) ? $_GET['interval'] : 'hourly';
$timeInterval = $requestedInterval === '5min' ? '5min' : 'hourly';
$requestedPanel = isset($_GET['panel']) ? trim((string) $_GET['panel']) : '';
$activePanel = in_array($requestedPanel, ['stats', 'graph'], true) ? $requestedPanel : '';

function loadStationOptions($metadataPath) {
    if (!is_file($metadataPath) || !is_readable($metadataPath)) {
        return [];
    }

    $decodedMetadata = json_decode(file_get_contents($metadataPath), true);
    if (!is_array($decodedMetadata)) {
        return [];
    }

    $stationOptions = [];
    foreach ($decodedMetadata as $stationCode => $stationInfo) {
        if (($stationInfo['Network_name'] ?? '') !== 'DEOS') {
            continue;
        }

        $code = strtoupper(trim((string) ($stationInfo['Name'] ?? $stationCode)));
        if ($code === '') {
            continue;
        }

        $label = trim((string) ($stationInfo['Description'] ?? ''));
        if ($label === '') {
            $label = $code;
        }

        $stationOptions[$code] = [
            'code' => $code,
            'label' => $label
        ];
    }

    uasort($stationOptions, function ($left, $right) {
        return strcasecmp($left['label'], $right['label']);
    });

    return $stationOptions;
}

$stationOptions = loadStationOptions(__DIR__ . '/metadata.json');
if (!isset($stationOptions[$station]) && !empty($stationOptions)) {
    $station = array_key_first($stationOptions);
}

$stationDisplayName = $stationOptions[$station]['label'] ?? $station;

// Format the title with the dates
$startDisplay = date('m/d/Y', strtotime($startDate));

$url = "https://services.cema.udel.edu/internal_services/api/data/{$station}/{$startDate}/{$endDate}?format=JSON&timezone=ET";

$validFlags = ['1', '3', '7'];

function normalizeUnits($units) {
    $units = trim(html_entity_decode(strip_tags((string)$units), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    $unitMap = [
        'K' => 'F',
        'm.s-1' => 'mph',
        'm.s-2' => 'm/s^2',
        'mm' => 'in',
        'mbar' => 'mb',
        'Rad' => 'deg',
        '%' => '%',
        'W.m-2' => 'W/m^2',
        'm' => 'ft'
    ];

    return $unitMap[$units] ?? $units;
}

function getParameterConfig($dataType, $rawUnits = '') {
    static $configMap = [
        'Air Temperature' => ['label' => 'Air Temp', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'avg'],
        'Barometric Pressure' => ['label' => 'Pressure', 'conversion' => 'none', 'precision' => 1, 'units' => 'mb', 'hourly_agg' => 'avg'],
        'Depth to Water' => ['label' => 'Water Depth', 'conversion' => 'm_to_ft', 'precision' => 2, 'units' => 'ft', 'hourly_agg' => 'avg'],
        'Dew Point Temperature' => ['label' => 'Dew Point', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'avg'],
        'Gage Precipitation (5)' => ['label' => 'Precip', 'conversion' => 'mm_to_inches', 'precision' => 2, 'units' => 'in', 'hourly_agg' => 'sum'],
        'Heat Index' => ['label' => 'Heat Index', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'max'],
        'Relative humidity' => ['label' => 'RH', 'conversion' => 'none', 'precision' => 0, 'units' => '%', 'hourly_agg' => 'avg'],
        'Soil Temperature (2 in.)' => ['label' => 'Soil Temp 2in', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'avg'],
        'Solar Radiation' => ['label' => 'Solar Rad', 'conversion' => 'none', 'precision' => 0, 'units' => 'W/m^2', 'hourly_agg' => 'avg'],
        'Water Temperature' => ['label' => 'Water Temp', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'avg'],
        'Wind Chill' => ['label' => 'Wind Chill', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'min'],
        'Wind Chill Temperature' => ['label' => 'Wind Chill', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'min'],
        'Wind Direction' => ['label' => 'Wind Dir', 'conversion' => 'rad_to_degrees', 'precision' => 0, 'units' => 'cardinal', 'hourly_agg' => 'direction'],
        'Wind Gust Speed (5)' => ['label' => 'Gust', 'conversion' => 'ms_to_mph', 'precision' => 1, 'units' => 'mph', 'hourly_agg' => 'max'],
        'Wind Speed' => ['label' => 'Wind Spd', 'conversion' => 'ms_to_mph', 'precision' => 1, 'units' => 'mph', 'hourly_agg' => 'avg']
    ];

    if (isset($configMap[$dataType])) {
        return $configMap[$dataType];
    }

    return [
        'label' => $dataType,
        'conversion' => 'none',
        'precision' => 2,
        'units' => normalizeUnits($rawUnits),
        'hourly_agg' => 'avg'
    ];
}

function convertValue($value, $conversionType) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            return ($value - 273.15) * 9 / 5 + 32;
        case 'ms_to_mph':
            return $value * 2.23694;
        case 'mm_to_inches':
            return $value * 0.0393701;
        case 'rad_to_degrees':
            return rad2deg($value);
        case 'm_to_ft':
            return $value * 3.28084;
        case 'none':
        default:
            return $value;
    }
}

function degreesToCardinal($degrees) {
    $degrees = fmod($degrees, 360.0);
    if ($degrees < 0) {
        $degrees += 360.0;
    }

    $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
    $shiftedDegrees = fmod($degrees + 11.25, 360.0);
    $index = (int) floor($shiftedDegrees / 22.5);

    return $directions[$index];
}

function formatValue($value, $config) {
    if (!is_numeric($value)) {
        return '-';
    }

    $convertedValue = convertValue((float)$value, $config['conversion']);

    if ($config['conversion'] === 'rad_to_degrees') {
        return degreesToCardinal($convertedValue);
    }

    return number_format($convertedValue, $config['precision'], '.', '');
}

function formatTimestampForDisplay($timestamp) {
    try {
        $dt = new DateTime($timestamp, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('America/New_York'));
        return $dt->format('m/d/Y h:i A');
    } catch (Exception $exception) {
        return $timestamp;
    }
}

function getColumnUnit($config) {
    return $config['units'] !== '' ? $config['units'] : '';
}

function getStatsDisplayValue($formattedValue, $config) {
    if ($formattedValue === '-' || $formattedValue === '') {
        return '-';
    }

    $unit = getColumnUnit($config);
    if ($unit === '' || $unit === 'cardinal') {
        return $formattedValue;
    }

    return $formattedValue . ' ' . $unit;
}

function getGraphUnit($config) {
    $unit = getColumnUnit($config);

    if ($unit === 'cardinal') {
        return 'deg';
    }

    return $unit;
}

function getTimeBucket(DateTimeImmutable $dateTime, $timeInterval) {
    if ($timeInterval === 'hourly') {
        $hourStart = $dateTime->setTime((int) $dateTime->format('H'), 0, 0);
        $hourEnd = $dateTime->setTime((int) $dateTime->format('H'), 59, 0);
        return [
            'key' => $hourStart->format('Y-m-d H:i:s'),
            'label' => $hourStart->format('m/d/Y h:i') . '-' . $hourEnd->format('h:i A')
        ];
    }

    return [
        'key' => $dateTime->format('Y-m-d H:i:s'),
        'label' => $dateTime->format('m/d/Y h:i A')
    ];
}

function getAggregationLabel($aggregationType) {
    switch ($aggregationType) {
        case 'sum':
            return 'sum';
        case 'avg':
        case 'direction':
            return 'mean';
        case 'min':
            return 'min';
        case 'max':
            return 'max';
        default:
            return '';
    }
}

function getMinimumRequiredObservations($aggregationType) {
    switch ($aggregationType) {
        case 'sum':
            return 12;
        case 'avg':
        case 'direction':
            return 10;
        case 'min':
        case 'max':
            return 1;
        default:
            return 1;
    }
}

function initializeAggregateState($aggregationType) {
    switch ($aggregationType) {
        case 'sum':
        case 'avg':
            return ['sum' => 0.0, 'count' => 0];
        case 'min':
        case 'max':
            return ['value' => null, 'count' => 0];
        case 'direction':
            return ['sin' => 0.0, 'cos' => 0.0, 'count' => 0];
        default:
            return ['sum' => 0.0, 'count' => 0];
    }
}

function addAggregateValue(&$state, $aggregationType, $value) {
    switch ($aggregationType) {
        case 'sum':
        case 'avg':
            $state['sum'] += $value;
            $state['count']++;
            break;
        case 'min':
            $state['count']++;
            if ($state['value'] === null || $value < $state['value']) {
                $state['value'] = $value;
            }
            break;
        case 'max':
            $state['count']++;
            if ($state['value'] === null || $value > $state['value']) {
                $state['value'] = $value;
            }
            break;
        case 'direction':
            $radians = deg2rad($value);
            $state['sin'] += sin($radians);
            $state['cos'] += cos($radians);
            $state['count']++;
            break;
    }
}

function finalizeAggregateValue($state, $aggregationType) {
    $minimumRequiredObservations = getMinimumRequiredObservations($aggregationType);

    switch ($aggregationType) {
        case 'sum':
            return $state['count'] >= $minimumRequiredObservations ? $state['sum'] : null;
        case 'avg':
            return $state['count'] >= $minimumRequiredObservations ? $state['sum'] / $state['count'] : null;
        case 'min':
        case 'max':
            return $state['count'] >= $minimumRequiredObservations ? $state['value'] : null;
        case 'direction':
            if ($state['count'] < $minimumRequiredObservations) {
                return null;
            }

            $averageRadians = atan2($state['sin'] / $state['count'], $state['cos'] / $state['count']);
            $averageDegrees = rad2deg($averageRadians);
            if ($averageDegrees < 0) {
                $averageDegrees += 360.0;
            }
            return $averageDegrees;
        default:
            return null;
    }
}

function formatConvertedValue($value, $config) {
    if ($value === null || !is_numeric($value)) {
        return '-';
    }

    if ($config['conversion'] === 'rad_to_degrees') {
        return degreesToCardinal((float) $value);
    }

    return number_format((float) $value, $config['precision'], '.', '');
}

function parseEasternDateTime($timestamp) {
    try {
        $dateTime = new DateTimeImmutable($timestamp, new DateTimeZone('UTC'));
        return $dateTime->setTimezone(new DateTimeZone('America/New_York'));
    } catch (Exception $exception) {
        return null;
    }
}

function getTimeHeaderLabel($timeInterval) {
    return $timeInterval === 'hourly' ? 'Hour' : 'Timestamp';
}

function finalizeStatsValue($state, $aggregationType) {
    switch ($aggregationType) {
        case 'sum':
            return $state['count'] > 0 ? $state['sum'] : null;
        case 'avg':
            return $state['count'] > 0 ? $state['sum'] / $state['count'] : null;
        case 'min':
        case 'max':
            return $state['count'] > 0 ? $state['value'] : null;
        case 'direction':
            if ($state['count'] === 0) {
                return null;
            }

            $averageRadians = atan2($state['sin'] / $state['count'], $state['cos'] / $state['count']);
            $averageDegrees = rad2deg($averageRadians);
            if ($averageDegrees < 0) {
                $averageDegrees += 360.0;
            }
            return $averageDegrees;
        default:
            return null;
    }
}

function downloadCsv($rows, $rowLabels, $columnOrder, $columns, $station, $startDate, $endDate, $timeInterval) {
    $safeStation = preg_replace('/[^A-Z0-9_\-]/', '', strtoupper($station));
    $safeStartDate = preg_replace('/[^0-9\-]/', '', $startDate);
    $safeEndDate = preg_replace('/[^0-9\-]/', '', $endDate);
    $filename = "daily_station_data_{$safeStation}_{$timeInterval}_{$safeStartDate}_to_{$safeEndDate}.csv";

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    $headerRow = [getTimeHeaderLabel($timeInterval) . ' (ET)'];
    foreach ($columnOrder as $dataType) {
        $unit = getColumnUnit($columns[$dataType]);
        $headerRow[] = $unit === ''
            ? $columns[$dataType]['label']
            : $columns[$dataType]['label'] . ' (' . $unit . ')';
    }
    fputcsv($output, $headerRow, ',', '"', '');

    foreach ($rows as $timestamp => $rowValues) {
        $csvRow = [$rowLabels[$timestamp] ?? $timestamp];
        foreach ($columnOrder as $dataType) {
            $csvRow[] = $rowValues[$dataType] ?? '-';
        }
        fputcsv($output, $csvRow, ',', '"', '');
    }

    fclose($output);
    exit;
}

function renderSharedHeader() {
    ob_start();
    include 'header.php';
    $headerMarkup = ob_get_clean();
    $headerMarkup = preg_replace('~<!DOCTYPE[^>]*>.*?<body[^>]*>~is', '', $headerMarkup);
    $headerMarkup = preg_replace('~</body>\s*</html>~is', '', $headerMarkup);
    echo $headerMarkup;
}

function renderSharedFooter() {
    include 'footer.php';
}

?>

<?php renderSharedHeader(); ?>

<div class='page-title-wrap'>
    <h1><?php echo htmlspecialchars($stationDisplayName); ?></h1>
    <p class='page-subtitle'><?php echo htmlspecialchars($startDisplay . ' • ' . ($timeInterval === 'hourly' ? 'Hourly' : '5-Minute')); ?></p>
</div>

<div class='controls'>
    <form method='GET'>
        <input type='hidden' name='panel' id='panelState' value='<?php echo htmlspecialchars($activePanel); ?>'>
        <button type='submit' id='applyFilters' hidden aria-hidden='true' tabindex='-1'>Apply filters</button>
        <div class='date-group'>
            <label for='station'>Station:</label>
            <select id='station' name='station' required aria-describedby='controls-note'>
                <?php foreach ($stationOptions as $stationOption): ?>
                    <option value='<?php echo htmlspecialchars($stationOption['code']); ?>' <?php echo $stationOption['code'] === $station ? 'selected' : ''; ?>><?php echo htmlspecialchars($stationOption['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class='date-group'>
            <label for='date'>Date:</label>
            <input type='date' id='date' name='date' value='<?php echo htmlspecialchars($startDate); ?>' required aria-describedby='controls-note'>
        </div>
        <div class='radio-group'>
            <span>Interval:</span>
            <label><input type='radio' name='interval' value='hourly' <?php echo $timeInterval !== '5min' ? 'checked' : ''; ?>>Hourly</label>
            <label><input type='radio' name='interval' value='5min' <?php echo $timeInterval === '5min' ? 'checked' : ''; ?>>5 Minute</label>
        </div>
        <button type='submit' name='download' value='csv'>Download CSV</button>
        <button type='button' id='toggleStats' aria-expanded='false' aria-controls='statsPanel'>Stats</button>
        <button type='button' id='toggleGraph' aria-expanded='false' aria-controls='graphPanel'>Graph</button>
        <!--<div class='controls-note' id='controls-note'></div>-->
    </form>
</div>

<?php

try {
    // Use cURL to make the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: $apiKey"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if ($error) {
        echo "cURL Error: " . $error . "\n";
    } elseif ($httpCode !== 200) {
        echo "HTTP Error: " . $httpCode . "\n";
        echo "Response: " . $response . "\n";
    } else {
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            $rows = [];
            $graphRows = [];
            $rowLabels = [];
            $columns = [];
            $aggregateStates = [];
            $statsStates = [];
            $statsCounts = [];
            $statsExpectedCount = 288;
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $station => $timestamps) {
                    if (!is_array($timestamps)) {
                        continue;
                    }

                    foreach ($timestamps as $timestamp => $measurements) {
                        if (!is_array($measurements)) {
                            continue;
                        }

                        foreach ($measurements as $measurementName => $measurement) {
                            if (!is_array($measurement)) {
                                continue;
                            }

                            $dataType = $measurement['data_type'] ?? $measurementName;
                            $value = $measurement['value'] ?? null;
                            $flag = (string)($measurement['flag'] ?? '');
                            $measurementTimestamp = $measurement['timestamp'] ?? $timestamp;

                            if (!isset($columns[$dataType])) {
                                $columns[$dataType] = getParameterConfig($dataType, $measurement['units'] ?? '');
                            }

                            $easternDateTime = parseEasternDateTime($measurementTimestamp);
                            if ($easternDateTime === null) {
                                continue;
                            }

                            $isSelectedDayMeasurement = $easternDateTime->format('Y-m-d') === $startDate;

                            $timeBucket = getTimeBucket($easternDateTime, $timeInterval);
                            $rowTimestamp = $timeBucket['key'];
                            $rowLabels[$rowTimestamp] = $timeBucket['label'];

                            if (!isset($rows[$rowTimestamp])) {
                                $rows[$rowTimestamp] = [];
                            }

                            if (!isset($graphRows[$rowTimestamp])) {
                                $graphRows[$rowTimestamp] = [];
                            }

                            if ($value === null || !in_array($flag, $validFlags, true) || !is_numeric($value)) {
                                if ($timeInterval !== 'hourly') {
                                    $rows[$rowTimestamp][$dataType] = '-';
                                }
                                continue;
                            }

                            $convertedValue = convertValue((float) $value, $columns[$dataType]['conversion']);

                            if ($isSelectedDayMeasurement) {
                                if (!isset($statsStates[$dataType])) {
                                    $statsStates[$dataType] = initializeAggregateState($columns[$dataType]['hourly_agg']);
                                }

                                addAggregateValue($statsStates[$dataType], $columns[$dataType]['hourly_agg'], $convertedValue);
                                $statsCounts[$dataType] = ($statsCounts[$dataType] ?? 0) + 1;
                            }

                            if ($timeInterval === 'hourly') {
                                if (!isset($aggregateStates[$rowTimestamp][$dataType])) {
                                    $aggregateStates[$rowTimestamp][$dataType] = initializeAggregateState($columns[$dataType]['hourly_agg']);
                                }

                                addAggregateValue($aggregateStates[$rowTimestamp][$dataType], $columns[$dataType]['hourly_agg'], $convertedValue);
                            } else {
                                $graphRows[$rowTimestamp][$dataType] = $convertedValue;
                                $rows[$rowTimestamp][$dataType] = formatConvertedValue($convertedValue, $columns[$dataType]);
                            }
                        }
                    }
                }
            }

            if ($timeInterval === 'hourly') {
                foreach ($aggregateStates as $rowTimestamp => $statesByType) {
                    if (!isset($rows[$rowTimestamp])) {
                        $rows[$rowTimestamp] = [];
                    }

                    foreach ($statesByType as $dataType => $state) {
                        $finalValue = finalizeAggregateValue($state, $columns[$dataType]['hourly_agg']);
                        $graphRows[$rowTimestamp][$dataType] = $finalValue;
                        $rows[$rowTimestamp][$dataType] = formatConvertedValue($finalValue, $columns[$dataType]);
                    }
                }

                $hourlyEndBoundary = $endDate . ' 00:00:00';
                foreach (array_keys($rows) as $rowTimestamp) {
                    if ($rowTimestamp >= $hourlyEndBoundary) {
                        unset($rows[$rowTimestamp], $graphRows[$rowTimestamp], $rowLabels[$rowTimestamp]);
                    }
                }
            }

            $preferredOrder = [
                'Wind Direction',
                'Wind Speed',
                'Wind Gust Speed (5)',
                'Air Temperature',
                'Dew Point Temperature',
                'Heat Index',
                'Relative humidity',
                'Barometric Pressure',
                'Gage Precipitation (5)',
                'Solar Radiation',
                'Soil Temperature (2 in.)',
                'Water Temperature',
                'Depth to Water'
            ];
            $preferredIndex = array_flip($preferredOrder);
            $columnOrder = array_keys($columns);
            usort($columnOrder, function($left, $right) use ($preferredIndex) {
                $leftIndex = $preferredIndex[$left] ?? PHP_INT_MAX;
                $rightIndex = $preferredIndex[$right] ?? PHP_INT_MAX;

                if ($leftIndex === $rightIndex) {
                    return strcasecmp($left, $right);
                }

                return $leftIndex <=> $rightIndex;
            });

            ksort($rows);
            ksort($graphRows);

            $statsRows = [];
            $graphConfig = [
                'labels' => [],
                'series' => []
            ];
            if (!empty($columnOrder)) {
                foreach ($columnOrder as $dataType) {
                    $aggregationType = $columns[$dataType]['hourly_agg'];
                    $statsRows[$dataType] = [
                        'label' => $columns[$dataType]['label'],
                        'summary_label' => getAggregationLabel($aggregationType),
                        'summary_value' => getStatsDisplayValue(
                            formatConvertedValue(
                                finalizeStatsValue($statsStates[$dataType] ?? initializeAggregateState($aggregationType), $aggregationType),
                                $columns[$dataType]
                            ),
                            $columns[$dataType]
                        ),
                        'completeness' => ($statsCounts[$dataType] ?? 0) . '/' . $statsExpectedCount
                    ];
                }

                foreach ($rows as $timestamp => $rowValues) {
                    $graphConfig['labels'][] = $rowLabels[$timestamp] ?? $timestamp;
                }

                foreach ($columnOrder as $dataType) {
                    $graphConfig['series'][$dataType] = [
                        'label' => $columns[$dataType]['label'],
                        'unit' => getGraphUnit($columns[$dataType]),
                        'graphType' => $dataType === 'Gage Precipitation (5)' ? 'bar' : 'line',
                        'checked' => in_array($dataType, ['Air Temperature', 'Wind Speed', 'Gage Precipitation (5)'], true),
                        'values' => []
                    ];

                    foreach ($rows as $timestamp => $rowValues) {
                        $graphConfig['series'][$dataType]['values'][] = isset($graphRows[$timestamp][$dataType]) && is_numeric($graphRows[$timestamp][$dataType])
                            ? round((float) $graphRows[$timestamp][$dataType], 4)
                            : null;
                    }
                }
            }

            if ($downloadFormat === 'csv') {
                downloadCsv($rows, $rowLabels, $columnOrder, $columns, $station, $startDate, $endDate, $timeInterval);
            }

            $countLabel = $timeInterval === 'hourly' ? 'Total Hours' : 'Total Timestamps';
      
            
            if (empty($rows) || empty($columnOrder)) {
                echo "<p>No parameter records were returned for the selected date range.</p>";
            } else {
                if (!empty($graphConfig['series'])) {
                    echo "<div class='graph-panel' id='graphPanel'>";
                    echo "<h2>Parameter Graph</h2>";
                    echo "<div class='graph-controls' id='graphControls'>";
                    foreach ($graphConfig['series'] as $dataType => $seriesConfig) {
                        echo "<label>";
                        echo "<input type='checkbox' value='" . htmlspecialchars($dataType) . "'" . ($seriesConfig['checked'] ? " checked" : "") . ">";
                        echo htmlspecialchars($seriesConfig['label']);
                        echo "</label>";
                    }
                    echo "</div>";
                    echo "<div class='graph-canvas-wrap'>";
                    echo "<canvas id='parameterChart'></canvas>";
                    echo "<div class='graph-empty' id='graphEmpty' style='display:none;'>Select at least one parameter to display the graph.</div>";
                    echo "</div>";
                    echo "<script id='graphData' type='application/json'>" . json_encode($graphConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . "</script>";
                    echo "</div>";
                }

                if (!empty($statsRows)) {
                    echo "<div class='stats-panel' id='statsPanel'>";
                    echo "<h2>5-Minute Parameter Stats</h2>";
                    echo "<div class='table-wrap'>";
                    echo "<table class='stats-table'>";
                    echo "<thead>";
                    echo "<tr><th>Parameter</th><th>Summary</th><th>Value</th><th>Completeness</th></tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    foreach ($statsRows as $statsRow) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($statsRow['label']) . "</td>";
                        echo "<td>" . htmlspecialchars($statsRow['summary_label']) . "</td>";
                        echo "<td>" . htmlspecialchars($statsRow['summary_value']) . "</td>";
                        echo "<td>" . htmlspecialchars($statsRow['completeness']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                    echo "</div>";
                    echo "</div>";
                }

                echo "<div class='table-wrap'>";
                echo "<table id='dataTable' class='data-table'>";
                echo "<thead>";
                echo "<tr><th>" . htmlspecialchars(getTimeHeaderLabel($timeInterval)) . "</th>";
                foreach ($columnOrder as $dataType) {
                    echo "<th>" . htmlspecialchars($columns[$dataType]['label']) . "</th>";
                }
                echo "</tr>";
                echo "<tr><th>ET</th>";
                foreach ($columnOrder as $dataType) {
                    echo "<th>" . htmlspecialchars(getColumnUnit($columns[$dataType])) . "</th>";
                }
                echo "</tr>";
                if ($timeInterval === 'hourly') {
                    echo "<tr class='agg-row'><th></th>";
                    foreach ($columnOrder as $dataType) {
                        echo "<th>" . htmlspecialchars(getAggregationLabel($columns[$dataType]['hourly_agg'])) . "</th>";
                    }
                    echo "</tr>";
                }
                echo "</thead>";
                echo "<tbody>";

                foreach ($rows as $timestamp => $rowValues) {
                    echo "<tr class='data-row'>";
                    echo "<td class='timestamp'>" . htmlspecialchars($rowLabels[$timestamp] ?? $timestamp) . "</td>";
                    foreach ($columnOrder as $dataType) {
                        echo "<td>" . htmlspecialchars($rowValues[$dataType] ?? '-') . "</td>";
                    }
                    echo "</tr>";
                }

                echo "</tbody>";
                echo "</table>";
                echo "</div>";
            }
        } else {
            echo "JSON decode error: " . json_last_error_msg();
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
if (isset($response)) {
   // echo htmlspecialchars(json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    echo "No response data available";
}
?>
</pre>

<?php renderSharedFooter(); ?>

<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var loadingOverlay = document.getElementById('pageLoading');
    var controlsForm = document.querySelector('.controls form');
    var applyFiltersButton = document.getElementById('applyFilters');
    var stationSelect = document.getElementById('station');
    var dateInput = document.getElementById('date');
    var initialDateValue = dateInput ? dateInput.value : '';
    var toggleButton = document.getElementById('toggleStats');
    var statsPanel = document.getElementById('statsPanel');
    var graphToggleButton = document.getElementById('toggleGraph');
    var graphPanel = document.getElementById('graphPanel');
    var panelStateInput = document.getElementById('panelState');
    var graphControls = document.getElementById('graphControls');
    var graphDataElement = document.getElementById('graphData');
    var graphEmpty = document.getElementById('graphEmpty');
    var graphCanvas = document.getElementById('parameterChart');
    var chartInstance = null;
    var graphData = null;

    function trackAnalyticsEvent(eventName, eventData) {
        if (typeof window.gtag !== 'function') {
            return;
        }

        window.gtag('event', eventName, eventData || {});
    }

    trackAnalyticsEvent('daily_view_page_load', {
        event_category: 'Daily View',
        event_label: 'Daily View Page Load',
        station_code: stationSelect ? stationSelect.value : '',
        station_name: stationSelect && stationSelect.selectedOptions.length ? stationSelect.selectedOptions[0].text : '',
        date: dateInput ? dateInput.value : '',
        interval: document.querySelector('input[name="interval"]:checked') ? document.querySelector('input[name="interval"]:checked').value : '',
        panel: panelStateInput ? panelStateInput.value : ''
    });

    function showLoadingState(message) {
        if (!body || !loadingOverlay) {
            return;
        }

        var loadingText = loadingOverlay.querySelector('.loading-text');
        if (loadingText && message) {
            loadingText.textContent = message;
        }

        body.classList.add('is-loading');
        loadingOverlay.setAttribute('aria-hidden', 'false');
    }

    function submitFilters() {
        if (!controlsForm) {
            return;
        }

        if (applyFiltersButton) {
            controlsForm.requestSubmit(applyFiltersButton);
            return;
        }

        controlsForm.requestSubmit();
    }

    function submitDateIfComplete() {
        if (!controlsForm || !dateInput) {
            return;
        }

        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateInput.value) || !dateInput.validity.valid) {
            return;
        }

        if (dateInput.value === initialDateValue) {
            return;
        }

        initialDateValue = dateInput.value;
        submitFilters();
    }

    function setCurrentPanel(panelName) {
        if (!panelStateInput) {
            return;
        }

        panelStateInput.value = panelName;
    }

    function setPanelState(button, panel, isVisible) {
        if (!button || !panel) {
            return;
        }

        panel.classList.toggle('is-visible', isVisible);
        button.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
    }

    function toggleExclusivePanel(activeButton, activePanel, inactiveButton, inactivePanel, activePanelName) {
        if (!activeButton || !activePanel) {
            return false;
        }

        var shouldShowActive = !activePanel.classList.contains('is-visible');

        setPanelState(activeButton, activePanel, shouldShowActive);

        if (inactiveButton && inactivePanel) {
            setPanelState(inactiveButton, inactivePanel, false);
        }

        setCurrentPanel(shouldShowActive ? activePanelName : '');

        return shouldShowActive;
    }

    if (graphDataElement) {
        try {
            graphData = JSON.parse(graphDataElement.textContent);
        } catch (error) {
            graphData = null;
        }
    }

    function createScaleKey(unit) {
        return 'scale_' + (unit || 'value').replace(/[^a-zA-Z0-9]+/g, '_').toLowerCase();
    }

    function createColor(index, alpha) {
        var palette = [
            [214, 39, 40],
            [31, 119, 180],
            [44, 160, 44],
            [255, 127, 14],
            [148, 103, 189],
            [140, 86, 75],
            [227, 119, 194],
            [127, 127, 127],
            [188, 189, 34],
            [23, 190, 207]
        ];
        var color = palette[index % palette.length];
        return 'rgba(' + color[0] + ', ' + color[1] + ', ' + color[2] + ', ' + alpha + ')';
    }

    function formatGraphAxisLabel(label) {
        if (typeof label !== 'string' || label.trim() === '') {
            return label;
        }

        var parts = label.split(' ');
        if (parts.length <= 1) {
            return label;
        }

        return parts.slice(1).join(' ');
    }

    function renderGraph() {
        if (!graphPanel || !graphControls || !graphCanvas || !graphData || typeof Chart === 'undefined') {
            return;
        }

        var selectedKeys = Array.prototype.map.call(
            graphControls.querySelectorAll('input[type="checkbox"]:checked'),
            function (checkbox) {
                return checkbox.value;
            }
        );

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        if (selectedKeys.length === 0) {
            graphCanvas.style.display = 'none';
            graphEmpty.style.display = 'block';
            return;
        }

        graphCanvas.style.display = 'block';
        graphEmpty.style.display = 'none';

        var datasets = [];
        var scales = {
            x: {
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 14
                }
            }
        };
        var scaleOrder = [];

        selectedKeys.forEach(function (key, index) {
            var series = graphData.series[key];
            if (!series) {
                return;
            }

            var unit = series.unit || 'value';
            var scaleKey = createScaleKey(unit);

            if (!scales[scaleKey]) {
                scaleOrder.push(scaleKey);
                scales[scaleKey] = {
                    type: 'linear',
                    display: true,
                    position: scaleOrder.length === 1 ? 'left' : 'right',
                    offset: scaleOrder.length > 2,
                    beginAtZero: unit === 'mph' || unit === 'in',
                    min: unit === 'mph' || unit === 'in' ? 0 : undefined,
                    title: {
                        display: true,
                        text: unit === 'value' ? 'Value' : unit
                    },
                    grid: {
                        drawOnChartArea: scaleOrder.length === 1
                    }
                };
            }

            datasets.push({
                label: series.unit ? series.label + ' (' + series.unit + ')' : series.label,
                data: series.values,
                type: series.graphType,
                yAxisID: scaleKey,
                borderColor: createColor(index, 1),
                backgroundColor: series.graphType === 'bar' ? createColor(index, 0.45) : createColor(index, 0.15),
                borderWidth: series.graphType === 'bar' ? 1 : 2,
                pointRadius: series.graphType === 'bar' ? 0 : (graphData.labels.length > 48 ? 0 : 2),
                pointHoverRadius: series.graphType === 'bar' ? 0 : 4,
                tension: series.graphType === 'bar' ? 0 : 0.2,
                spanGaps: true,
                order: series.graphType === 'bar' ? 2 : 1
            });
        });

        chartInstance = new Chart(graphCanvas, {
            type: 'line',
            data: {
                labels: graphData.labels.map(formatGraphAxisLabel),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                stacked: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            title: function (tooltipItems) {
                                if (!tooltipItems.length) {
                                    return '';
                                }

                                return graphData.labels[tooltipItems[0].dataIndex] || '';
                            }
                        }
                    }
                },
                scales: Object.assign({}, scales, {
                    x: Object.assign({}, scales.x, {
                        ticks: Object.assign({}, scales.x.ticks, {
                            maxRotation: 50,
                            minRotation: 50,
                            padding: 8,
                            font: {
                                size: 13
                            }
                        })
                    })
                })
            }
        });
    }

    if (toggleButton && statsPanel) {
        toggleButton.addEventListener('click', function () {
            var isVisible = toggleExclusivePanel(toggleButton, statsPanel, graphToggleButton, graphPanel, 'stats');
            trackAnalyticsEvent('daily_view_panel_toggle', {
                event_category: 'Daily View',
                event_label: 'Stats Panel Toggle',
                panel: 'stats',
                state: isVisible ? 'open' : 'closed'
            });
        });
    }

    if (graphToggleButton && graphPanel) {
        graphToggleButton.addEventListener('click', function () {
            var isVisible = toggleExclusivePanel(graphToggleButton, graphPanel, toggleButton, statsPanel, 'graph');
            trackAnalyticsEvent('daily_view_panel_toggle', {
                event_category: 'Daily View',
                event_label: 'Graph Panel Toggle',
                panel: 'graph',
                state: isVisible ? 'open' : 'closed'
            });
            if (isVisible) {
                renderGraph();
            }
        });
    }

    if (graphControls) {
        graphControls.addEventListener('change', function (event) {
            if (event.target && event.target.matches('input[type="checkbox"]')) {
                renderGraph();
            }
        });
    }

    if (stationSelect) {
        stationSelect.addEventListener('change', function () {
            submitFilters();
        });
    }

    if (controlsForm) {
        controlsForm.querySelectorAll('input[name="interval"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                submitFilters();
            });
        });
    }

    if (panelStateInput) {
        if (panelStateInput.value === 'stats' && toggleButton && statsPanel) {
            setPanelState(toggleButton, statsPanel, true);
            if (graphToggleButton && graphPanel) {
                setPanelState(graphToggleButton, graphPanel, false);
            }
        } else if (panelStateInput.value === 'graph' && graphToggleButton && graphPanel) {
            setPanelState(graphToggleButton, graphPanel, true);
            if (toggleButton && statsPanel) {
                setPanelState(toggleButton, statsPanel, false);
            }
            renderGraph();
        } else {
            setCurrentPanel('');
        }
    }

    if (dateInput) {
        dateInput.addEventListener('blur', submitDateIfComplete);
        dateInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitDateIfComplete();
            }
        });
    }

    if (controlsForm) {
        controlsForm.addEventListener('submit', function (event) {
            var submitter = event.submitter || null;
            if (submitter && submitter.name === 'download') {
                trackAnalyticsEvent('daily_view_download', {
                    event_category: 'Daily View',
                    event_label: 'Daily View Download CSV',
                    station_code: stationSelect ? stationSelect.value : '',
                    station_name: stationSelect && stationSelect.selectedOptions.length ? stationSelect.selectedOptions[0].text : '',
                    date: dateInput ? dateInput.value : '',
                    interval: document.querySelector('input[name="interval"]:checked') ? document.querySelector('input[name="interval"]:checked').value : '',
                    panel: panelStateInput ? panelStateInput.value : ''
                });
                return;
            }

            showLoadingState('Fetching the selected station and date. This can take a moment.');
        });
    }
});
</script>

</body>
</html>
