<?php
ob_start();
require_once __DIR__ . '/daily_view_helpers.php';
?>
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
    <link rel='stylesheet' href='assets/daily_view.css'>
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
$dataLoadError = '';

$defaultSelectedDate = '2025-07-30';
$rawSelectedDate = isset($_GET['date'])
    ? trim((string) $_GET['date'])
    : (isset($_GET['startDate']) ? trim((string) $_GET['startDate']) : $defaultSelectedDate);

$parsedSelectedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $rawSelectedDate);
$dateErrors = DateTimeImmutable::getLastErrors();
$hasDateErrors = is_array($dateErrors)
    && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0);

if (!($parsedSelectedDate instanceof DateTimeImmutable) || $hasDateErrors || $parsedSelectedDate->format('Y-m-d') !== $rawSelectedDate) {
    error_log('daily_view.php: invalid date parameter received: ' . $rawSelectedDate);
    $parsedSelectedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $defaultSelectedDate);
}

// Get dates from form or use defaults
$station = isset($_GET['station']) && trim($_GET['station']) !== '' ? strtoupper(trim($_GET['station'])) : 'DAGF';
$type = isset($_GET['type']) ? $_GET['type'] : 'meteorological';

// Check if station has restricted water data
$restrictedWaterDataStations = ['DAGF', 'DWAR', 'DSND', 'DJCR'];
$stationHasRestrictedWaterData = in_array($station, $restrictedWaterDataStations);

$selectedDate = $parsedSelectedDate->format('Y-m-d');
$startDate = $selectedDate;
$endDate = $parsedSelectedDate->modify('+1 day')->format('Y-m-d');
$downloadFormat = isset($_GET['download']) ? $_GET['download'] : '';
$requestedInterval = isset($_GET['interval']) ? $_GET['interval'] : 'hourly';
$timeInterval = $requestedInterval === '5min' ? '5min' : 'hourly';
$requestedPanel = isset($_GET['panel']) ? trim((string) $_GET['panel']) : '';
$requestedPanels = $requestedPanel !== ''
    ? array_map('trim', explode(',', $requestedPanel))
    : [];
$activePanels = array_values(array_intersect($requestedPanels, ['stats', 'graph', 'table']));
if (empty($activePanels)) {
    $activePanels = ['table'];
}
$activePanelState = implode(',', $activePanels);

$stationOptions = loadStationOptions('https://services.cema.udel.edu/internal_services/api/station_metadata/DEOS', $apiKey, $type);
if (!isset($stationOptions[$station]) && !empty($stationOptions)) {
    $station = array_key_first($stationOptions);
}

$stationDisplayName = $stationOptions[$station]['label'] ?? $station;

// Format the title with the dates
$startDisplay = $parsedSelectedDate->format('m/d/Y');

$url = "https://services.cema.udel.edu/internal_services/api/data/{$station}/{$startDate}/{$endDate}?format=JSON&timezone=ET";

$validFlags = ['1', '3', '7'];

?>

<?php renderSharedHeader(); ?>

<div class='page-title-wrap'>
    <div class='title-header'>
        <h1><?php echo htmlspecialchars($stationDisplayName); ?></h1>
        <p class='page-subtitle'><?php echo htmlspecialchars($startDisplay . ' • ' . ($timeInterval === 'hourly' ? 'Hourly' : '5-Minute')); ?></p>
    </div>
    <button type='button' id='infoButton' class='info-button' title='Parameter Information' aria-label='Open parameter information' aria-expanded='false' aria-controls='infoModal'><i>i</i></button>
</div>

<div id='infoModal' class='info-modal' role='dialog' aria-labelledby='infoModalTitle' aria-hidden='true'>
    <div class='modal-overlay'></div>
    <div class='modal-content'>
        <div class='modal-header'>
            <h2 id='infoModalTitle'>About This Data</h2>
            <button type='button' id='closeInfoModal' class='close-button' title='Close' aria-label='Close information panel'>
                <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                    <line x1='18' y1='6' x2='6' y2='18'></line>
                    <line x1='6' y1='6' x2='18' y2='18'></line>
                </svg>
            </button>
        </div>
        <div class='modal-body'>
            <section class='info-section'>
                <h3>Data Resolution & Hourly Values</h3>
                <p><strong>5-minute data</strong> is the native resolution of our network. All measurements are recorded at 5-minute intervals from the source instruments.</p>
                <p><strong>Hourly values</strong> are derived from the 5-minute data using completion thresholds. This means:</p>
                <ul>
                    <li><strong>For averaging (mean):</strong> At least 10 observations required in the hour</li>
                    <li><strong>For summation (precipitation):</strong> At least 12 observations required in the hour</li>
                    <li><strong>For extremes (min/max):</strong> At least 1 observation required in the hour</li>
                </ul>
                <p>If the threshold is not met, the hourly value is not calculated and reported as missing.</p>
            </section>

            <section class='info-section'>
                <h3>Parameter Descriptions</h3>
                <?php $descriptions = getParameterDescriptions(); ?>
                <?php foreach ($descriptions as $category => $parameters): ?>
                    <div class='parameter-category'>
                        <h4><?php echo htmlspecialchars($category); ?></h4>
                        <dl class='parameter-list'>
                            <?php foreach ($parameters as $paramName => $description): ?>
                                <dt><?php echo htmlspecialchars($paramName); ?></dt>
                                <dd><?php echo htmlspecialchars($description); ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class='info-section'>
                <h3>Understanding the Data Tables</h3>
                <div class='info-subsection'>
                    <h4>Completeness Column</h4>
                    <p>Shows the number of valid 5-minute observations included in the daily statistics. For example, "287/288" means 287 out of 288 possible 5-minute intervals had valid data that day (288 intervals = 24 hours × 12 intervals per hour).</p>
                </div>
                <div class='info-subsection'>
                    <h4>Unit Conversions</h4>
                    <p>Temperature values from the network are converted from Kelvin to Fahrenheit. Precipitation is converted from millimeters to inches. Wind speed is converted from meters per second to miles per hour. Wind direction is converted from radians to cardinal directions (N, NNE, NE, etc.).</p>
                </div>
                <div class='info-subsection'>
                    <h4>Aggregation Methods</h4>
                    <p><strong>Mean (avg):</strong> Average value of the 5-minute observations in the period.</p>
                    <p><strong>Sum:</strong> Total accumulated value (used for precipitation). Represents cumulative rainfall over the hour.</p>
                    <p><strong>Min:</strong> Lowest value observed in the period.</p>
                    <p><strong>Max:</strong> Highest value observed in the period.</p>
                </div>
            </section>

            <section class='info-section'>
                <h3>Data Quality Notes</h3>
                <p>All data displayed on this page has been screened for quality and validity. Missing or invalid measurements appear as "-" in the tables. Gaps in the data may indicate instrument malfunction, maintenance, or network issues during that period.</p>
            </section>
        </div>
    </div>
</div>

<div class='controls'>
    <form method='GET'>
        <input type='hidden' name='panel' id='panelState' value='<?php echo htmlspecialchars($activePanelState); ?>'>
        <input type='hidden' name='graph' id='graphState' value=''>
        <button type='submit' id='applyFilters' hidden aria-hidden='true' tabindex='-1'>Apply filters</button>
        <div class='date-group'>
            <label for='station'>Station:</label>
            <select id='station' name='station' required aria-describedby='controls-note'>
                <?php foreach ($stationOptions as $stationOption): ?>
                    <option value='<?php echo htmlspecialchars($stationOption['code']); ?>' <?php echo $stationOption['code'] === $station ? 'selected' : ''; ?>><?php echo htmlspecialchars($stationOption['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class='radio-group'>
            <span>Type:</span>
            <label><input type='radio' name='type' value='meteorological' <?php echo $type !== 'hydrological' ? 'checked' : ''; ?>>Meteorological</label>
            <label><input type='radio' name='type' value='hydrological' <?php echo $type === 'hydrological' ? 'checked' : ''; ?>>Hydrological</label>
        </div>
        <div class='date-group'>
            <label for='date'>Date:</label>
            <button type='button' id='prevDate' title='Previous Day' aria-label='Previous Day'>-</button>
            <input type='date' id='date' name='date' value='<?php echo htmlspecialchars($startDate); ?>' required aria-describedby='controls-note'>
            <button type='button' id='nextDate' title='Next Day' aria-label='Next Day'>+</button>
        </div>
        <div class='radio-group'>
            <span>Interval:</span>
            <label><input type='radio' name='interval' value='hourly' <?php echo $timeInterval !== '5min' ? 'checked' : ''; ?>>Hourly</label>
            <label><input type='radio' name='interval' value='5min' <?php echo $timeInterval === '5min' ? 'checked' : ''; ?>>5 Minute</label>
        </div>
        <button type='submit' name='download' value='csv'>Download CSV</button>
        <button type='button' id='toggleStats' aria-expanded='false' aria-controls='statsPanel'><span class='toggle-check' aria-hidden='true'></span><span>Stats</span></button>
        <button type='button' id='toggleGraph' aria-expanded='false' aria-controls='graphPanel'><span class='toggle-check' aria-hidden='true'></span><span>Graph</span></button>
        <button type='button' id='toggleTable' aria-expanded='false' aria-controls='tablePanel'><span class='toggle-check' aria-hidden='true'></span><span>Table</span></button>
        <!--<div class='controls-note' id='controls-note'></div>-->
    </form>
</div>

<?php if ($stationHasRestrictedWaterData): ?>
<div class='data-notice'>
    <p><small>Well depth and water temperature information is available by contacting the <a href='https://dnrec.delaware.gov/geological-survey/' target='_blank' rel='noopener'>Delaware Geological Survey</a>.</small></p>
</div>
<?php endif; ?>

<?php

try {
    // Use cURL to make the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    configureCurlTlsOptions($ch);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: $apiKey"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if ($response === false || $error !== '') {
        error_log('daily_view.php: data API cURL error for ' . $station . ' on ' . $startDate . ': ' . $error);
        $dataLoadError = 'Unable to load subdaily data right now. Please try again shortly.';
    } elseif ($httpCode !== 200) {
        error_log('daily_view.php: data API returned HTTP ' . $httpCode . ' for ' . $station . ' on ' . $startDate);
        $dataLoadError = 'Unable to load subdaily data right now. Please try again shortly.';
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

            // Filter out water data columns for restricted stations
            if ($stationHasRestrictedWaterData) {
                $columnOrder = array_filter($columnOrder, function($col) {
                    return $col !== 'Water Temperature' && $col !== 'Depth to Water';
                });
                $columnOrder = array_values($columnOrder); // Re-index array
            }

            ksort($rows);
            ksort($graphRows);

            $statsRows = [];
            $graphConfig = [
                'labels' => [],
                'series' => [],
                'station' => $station,
                'date' => $startDate
            ];
            if (!empty($columnOrder)) {
                $defaultGraphParameter = in_array('Air Temperature', $columnOrder, true)
                    ? 'Air Temperature'
                    : $columnOrder[0];

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
                        'checked' => $dataType === $defaultGraphParameter,
                        'values' => []
                    ];

                    foreach ($rows as $timestamp => $rowValues) {
                        $graphConfig['series'][$dataType]['values'][] = isset($graphRows[$timestamp][$dataType]) && is_numeric($graphRows[$timestamp][$dataType])
                            ? round((float) $graphRows[$timestamp][$dataType], $columns[$dataType]['precision'])
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

                if (!empty($graphConfig['series'])) {
                    echo "<div class='graph-panel' id='graphPanel'>";
                    echo "<h2>Parameter Graph</h2>";
                    echo "<div class='graph-controls-wrapper'>";
                    echo "<div class='graph-controls' id='graphControls'>";
                    foreach ($graphConfig['series'] as $dataType => $seriesConfig) {
                        echo "<label>";
                        echo "<input type='checkbox' value='" . htmlspecialchars($dataType) . "'" . ($seriesConfig['checked'] ? " checked" : "") . ">";
                        echo htmlspecialchars($seriesConfig['label']);
                        echo "</label>";
                    }
                    echo "</div>";
                    echo "<button id='downloadGraph' type='button' title='Download Graph' aria-label='Download Graph' class='download-graph-button'>";
                    echo "<svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>";
                    echo "<path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'></path>";
                    echo "<polyline points='7,10 12,15 17,10'></polyline>";
                    echo "<line x1='12' y1='15' x2='12' y2='3'></line>";
                    echo "</svg>";
                    echo "</button>";
                    echo "</div>";
                    echo "<div class='graph-canvas-wrap'>";
                    echo "<canvas id='parameterChart'></canvas>";
                    echo "<div class='graph-empty' id='graphEmpty' style='display:none;'>Select at least one parameter to display the graph.</div>";
                    echo "</div>";
                    echo "<script id='graphData' type='application/json'>" . json_encode($graphConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . "</script>";
                    echo "</div>";
                }

                echo "<div class='table-wrap table-panel' id='tablePanel'>";
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
            error_log('daily_view.php: JSON decode error for ' . $station . ' on ' . $startDate . ': ' . json_last_error_msg());
            $dataLoadError = 'Unable to load subdaily data right now. Please try again shortly.';
        }
    }
} catch (Exception $e) {
    error_log('daily_view.php: unexpected error for ' . $station . ' on ' . $startDate . ': ' . $e->getMessage());
    $dataLoadError = 'Unable to load subdaily data right now. Please try again shortly.';
}

if ($dataLoadError !== '') {
    echo '<p>' . htmlspecialchars($dataLoadError) . '</p>';
}
?>

<?php renderSharedFooter(); ?>

<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'></script>
<script src='assets/daily_view.js'></script>

</body>
</html>
