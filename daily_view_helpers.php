<?php

function isLocalDevelopmentRequest() {
    $serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
    $httpHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $remoteAddr = strtolower((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $serverAddr = strtolower((string) ($_SERVER['SERVER_ADDR'] ?? ''));

    $localHosts = ['localhost', '127.0.0.1', '::1'];

    foreach ([$serverName, $httpHost, $remoteAddr, $serverAddr] as $value) {
        $normalizedValue = trim(explode(':', $value)[0]);
        if (in_array($normalizedValue, $localHosts, true)) {
            return true;
        }
    }

    return false;
}

function configureCurlTlsOptions($ch) {
    if (isLocalDevelopmentRequest()) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        return;
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}

function loadStationOptions($metadataSource, $apiKey = null, $type = 'all') {
    if (preg_match('/^https?:\/\//i', (string) $metadataSource)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $metadataSource);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        configureCurlTlsOptions($ch);

        $headers = [];
        if ($apiKey !== null && $apiKey !== '') {
            $headers[] = 'x-api-key: ' . $apiKey;
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($response === false || $error !== '' || $httpCode !== 200) {
            return [];
        }

        $decodedMetadata = json_decode($response, true);
    } else {
        if (!is_file($metadataSource) || !is_readable($metadataSource)) {
            return [];
        }

        $decodedMetadata = json_decode(file_get_contents($metadataSource), true);
    }

    if (!is_array($decodedMetadata)) {
        return [];
    }

    if (isset($decodedMetadata['data']) && is_array($decodedMetadata['data'])) {
        $decodedMetadata = $decodedMetadata['data'];
    }

    $stationOptions = [];
    foreach ($decodedMetadata as $stationCode => $stationInfo) {
        if (!is_array($stationInfo)) {
            continue;
        }

        if ($type === 'meteorological' && ($stationInfo['weather_station'] ?? $stationInfo['Weather_Station'] ?? 'N') !== 'Y') {
            continue;
        }
        if ($type === 'hydrological' && !in_array('Y', [
            $stationInfo['streamflow_station'] ?? $stationInfo['Streamflow_Station'] ?? 'N',
            $stationInfo['tidal_station'] ?? $stationInfo['Tidal_Station'] ?? 'N',
            $stationInfo['waterquality_station'] ?? $stationInfo['Waterquality_Station'] ?? 'N',
            $stationInfo['groundwater_station'] ?? $stationInfo['Groundwater_Station'] ?? 'N',
            $stationInfo['waterbouy_station'] ?? $stationInfo['Waterbouy_Station'] ?? 'N'
        ])) {
            continue;
        }

        $code = strtoupper(trim((string) ($stationInfo['station_name'] ?? $stationInfo['Name'] ?? $stationCode)));
        if ($code === '') {
            continue;
        }

        $label = trim((string) ($stationInfo['description'] ?? $stationInfo['Description'] ?? ''));
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

function normalizeUnits($units) {
    $units = trim(html_entity_decode(strip_tags((string) $units), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

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

function getParameterDescriptions() {
    return [
        'Temperature Parameters' => [
            'Air Temperature' => 'The ambient air temperature at 2 meters above the ground surface.',
            'Dew Point Temperature' => 'The temperature at which air becomes saturated and water vapor begins to condense. Lower values indicate drier conditions.',
            'Heat Index' => 'The "feels like" temperature that combines actual air temperature and humidity. Higher values indicate more oppressive heat stress.',
            'Wind Chill' => 'The "feels like" temperature that combines actual air temperature and wind speed. Lower values indicate increased frostbite risk.',
            'Water Temperature' => 'The temperature of water at the measurement location.',
            'Soil Temperature (2 in.)' => 'The temperature of soil at 2 inches below the ground surface.'
        ],
        'Wind Parameters' => [
            'Wind Speed' => 'The average speed of horizontal wind movement, typically measured at 3 meters above ground. Used for meteorological analysis and wind resource assessment.',
            'Wind Gust Speed (5)' => 'The maximum wind speed measured in the preceding 5-minute interval. Indicates sudden wind events that could affect operations.',
            'Wind Direction' => 'The compass direction from which the wind is blowing (N, NNE, NE, etc.). Measured in degrees clockwise from north (0°).'
        ],
        'Moisture & Pressure Parameters' => [
            'Relative humidity' => 'The ratio of actual water vapor in the air to the maximum amount air can hold at that temperature, expressed as a percentage.',
            'Barometric Pressure' => 'The atmospheric pressure at the measurement location. Changes indicate weather system movement and potential weather changes.',
            'Gage Precipitation (5)' => 'Rainfall measured with a tipping bucket gauge over the preceding 5-minute interval. Raw precipitation values before hourly aggregation.'
        ],
        'Solar & Water Parameters' => [
            'Solar Radiation' => 'The amount of solar electromagnetic radiation striking a horizontal surface. Important for agriculture, solar energy, and evapotranspiration calculations.',
            'Depth to Water' => 'The distance from the land surface to the water table, typically measured in groundwater monitoring wells.'
        ]
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
    $filename = "daily_station_data_{$safeStation}_{$timeInterval}_{$safeStartDate}.csv";

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