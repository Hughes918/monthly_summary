<?php ob_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        .controls { margin-bottom: 20px; padding: 15px; background-color: #f5f5f5; border-radius: 5px; }
        .controls input[type="date"] { padding: 8px; margin: 0 10px; font-size: 14px; }
        .controls input[type="radio"] { margin-right: 6px; }
        .controls button { padding: 8px 20px; font-size: 14px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .controls button:hover { background-color: #45a049; }
        .submit-button { margin-right: 14px; }
        .date-group { display: inline-block; margin-right: 20px; }
        .radio-group { display: inline-flex; align-items: center; gap: 8px; margin-right: 20px; }
        .radio-group label { margin-right: 12px; }
        .table-wrap { overflow-x: auto; max-width: 100%; border: 1px solid #ccc; }
        .data-table { border-collapse: collapse; width: 100%; min-width: 1200px; }
        .stats-table { border-collapse: collapse; width: 100%; min-width: 700px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        td { white-space: nowrap; }
        th { background-color: #4CAF50; color: white; }
        thead th { white-space: normal; line-height: 1.2; vertical-align: bottom; }
        thead tr:first-child th { padding-bottom: 4px; }
        thead tr:last-child th { padding-top: 4px; font-size: 12px; font-weight: normal; }
        .agg-row th { background-color: #3a8040; font-size: 12px; font-weight: normal; }
        .graph-panel { display: none; margin-bottom: 18px; padding: 16px; border: 1px solid #ccc; background-color: #fff; }
        .graph-panel.is-visible { display: block; }
        .graph-panel h2 { margin: 0 0 8px; font-size: 20px; }
        .graph-panel p { margin: 0 0 12px; color: #555; }
        .graph-controls { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .graph-controls label { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1px solid #d4d4d4; border-radius: 4px; background-color: #f9f9f9; }
        .graph-controls input[type="checkbox"] { margin: 0; }
        .graph-canvas-wrap { position: relative; min-height: 505px; }
        .graph-empty { padding: 24px; color: #555; text-align: center; border: 1px dashed #ccc; background-color: #fafafa; }
        .stats-panel { display: none; margin-bottom: 18px; }
        .stats-panel.is-visible { display: block; }
        .stats-panel h2 { margin: 0 0 12px; font-size: 20px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f0f0f0; }
        .timestamp { font-weight: bold; }
    </style>
</head>
<body>

<?php

$apiKey = 'C7384045980DCA07FD2036B439448C1A281132329DE17A8CD8F03810091AF107';

// Get dates from form or use defaults
$station = isset($_GET['station']) && trim($_GET['station']) !== '' ? strtoupper(trim($_GET['station'])) : 'DAGF';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : (isset($_GET['startDate']) ? $_GET['startDate'] : '2025-07-30');
$startDate = $selectedDate;
$endDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
$downloadFormat = isset($_GET['download']) ? $_GET['download'] : '';
$timeInterval = (isset($_GET['interval']) && $_GET['interval'] === 'hourly') ? 'hourly' : '5min';

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
        'Gage Precipitation (5)' => ['label' => 'Precip (5)', 'conversion' => 'mm_to_inches', 'precision' => 2, 'units' => 'in', 'hourly_agg' => 'sum'],
        'Heat Index' => ['label' => 'Heat Index', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'max'],
        'Relative humidity' => ['label' => 'RH', 'conversion' => 'none', 'precision' => 0, 'units' => '%', 'hourly_agg' => 'avg'],
        'Soil Temperature (2 in.)' => ['label' => 'Soil Temp 2in', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'avg'],
        'Solar Radiation' => ['label' => 'Solar Rad', 'conversion' => 'none', 'precision' => 0, 'units' => 'W/m^2', 'hourly_agg' => 'avg'],
        'Water Temperature' => ['label' => 'Water Temp', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'avg'],
        'Wind Chill' => ['label' => 'Wind Chill', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'min'],
        'Wind Chill Temperature' => ['label' => 'Wind Chill', 'conversion' => 'kelvin_to_fahrenheit', 'precision' => 1, 'units' => 'F', 'hourly_agg' => 'min'],
        'Wind Direction' => ['label' => 'Wind Dir', 'conversion' => 'rad_to_degrees', 'precision' => 0, 'units' => 'cardinal', 'hourly_agg' => 'direction'],
        'Wind Gust Speed (5)' => ['label' => 'Gust (5)', 'conversion' => 'ms_to_mph', 'precision' => 1, 'units' => 'mph', 'hourly_agg' => 'max'],
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

<h1><?php echo htmlspecialchars(($timeInterval === 'hourly' ? 'Hourly' : '5-Minute') . ' Station Data - ' . $station . ' - ' . $startDisplay); ?></h1>

<div class='controls'>
    <form method='GET'>
        <div class='date-group'>
            <label for='station'>Station:</label>
            <input type='text' id='station' name='station' value='<?php echo htmlspecialchars($station); ?>' maxlength='10' required>
        </div>
        <div class='date-group'>
            <label for='date'>Date:</label>
            <input type='date' id='date' name='date' value='<?php echo htmlspecialchars($startDate); ?>' required>
        </div>
        <button type='submit' class='submit-button'>Submit</button>
        <div class='radio-group'>
            <span>Interval:</span>
            <label><input type='radio' name='interval' value='5min' onchange='this.form.requestSubmit();' <?php echo $timeInterval === '5min' ? 'checked' : ''; ?>>5 Minute</label>
            <label><input type='radio' name='interval' value='hourly' onchange='this.form.requestSubmit();' <?php echo $timeInterval === 'hourly' ? 'checked' : ''; ?>>Hourly</label>
        </div>
        <button type='submit' name='download' value='csv'>Download CSV</button>
        <button type='button' id='toggleStats' aria-expanded='false' aria-controls='statsPanel'>Stats</button>
        <button type='button' id='toggleGraph' aria-expanded='false' aria-controls='graphPanel'>Graph</button>
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
    var toggleButton = document.getElementById('toggleStats');
    var statsPanel = document.getElementById('statsPanel');
    var graphToggleButton = document.getElementById('toggleGraph');
    var graphPanel = document.getElementById('graphPanel');
    var graphControls = document.getElementById('graphControls');
    var graphDataElement = document.getElementById('graphData');
    var graphEmpty = document.getElementById('graphEmpty');
    var graphCanvas = document.getElementById('parameterChart');
    var chartInstance = null;
    var graphData = null;

    function togglePanel(button, panel) {
        if (!button || !panel) {
            return false;
        }

        var isVisible = panel.classList.toggle('is-visible');
        button.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
        return isVisible;
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
            togglePanel(toggleButton, statsPanel);
        });
    }

    if (graphToggleButton && graphPanel) {
        graphToggleButton.addEventListener('click', function () {
            var isVisible = togglePanel(graphToggleButton, graphPanel);
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
});
</script>

</body>
</html>
