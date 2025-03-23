<?php
header('Content-Type: text/html; charset=UTF-8');

// Retrieve query parameters
$queryString = $_SERVER['QUERY_STRING'] ?? (isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) : null);

if (!empty($queryString)) {
    parse_str($queryString, $queryParams);
    $station_name = !empty($queryParams['station']) ? htmlspecialchars($queryParams['station']) : 'DAGF';
    $date = !empty($queryParams['date']) ? htmlspecialchars($queryParams['date']) : strtoupper(date('M')) . '_' . date('Y');
} else {
    $station_name = 'DXXX';
    $date = strtoupper(date('M')) . '_' . date('Y');
}

// Validate and convert date
if (strpos($date, '_') !== false) {
    list($month, $year) = explode('_', $date);
    $dateObj = DateTime::createFromFormat('M', ucfirst(strtolower($month)));

    if ($dateObj && is_numeric($year)) {
        $monthNumber = $dateObj->format('m');
        $start_date  = "$year-$monthNumber-01";
        $end_date    = date("Y-m-t", strtotime($start_date));
        // JSON data URL
        $jsonFile = "http://150.136.239.199/almanac/retrieve.old.php?start=$start_date&end=$end_date&station_name=$station_name&raw_values=Y";
    } else {
        die("Invalid month or year format provided.<br>");
    }
} else {
    die("Date must be in the format 'MMM_YYYY', e.g., 'JUN_2023'.<br>");
}

$jsonData = @file_get_contents($jsonFile);
$data     = json_decode($jsonData, true);
if ($data === null) {
    // Instead of erroring out, set empty data
    $data = ['data' => []];
}

/**
 * Metadata array with conditional sum/mean.
 * Note: Removed second 'Gage Precipitation (Daily)' in 'ag' to avoid duplicating precipitation.
 * Now including Mean Wind Direction's monthly average by setting display_mean => 'Yes'.
 */
$metadata = [
    // BASIC
    [
        "data_name_full"    => "Mean Daily Temp.",
        "data_name_display" => "Mean Temp.",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Max Daily Temp.",
        "data_name_display" => "Max Temp.",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Min Daily Temp.",
        "data_name_display" => "Min Temp.",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Mean Wind Speed",
        "data_name_display" => "Wind Speed",
        "conversion_type"   => "ms_to_mph",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "mph"
    ],
    [
        "data_name_full"    => "Mean Wind Direction",
        "data_name_display" => "Wind Dir.",
        "conversion_type"   => "rad_to_degrees",
        "precision_type"    => 0,
        "view_type"         => "text",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => ""  // cardinal direction; no unit needed
    ],
    [
        "data_name_full"    => "Peak Wind Gust Speed (Daily)",
        "data_name_display" => "Max Gust",
        "conversion_type"   => "ms_to_mph",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "No",
        "display_units"     => "mph"
    ],
    [
        "data_name_full"    => "Gage Precipitation (Daily)",
        "data_name_display" => "Precip",
        "conversion_type"   => "mm_to_inches",
        "precision_type"    => 2,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "Yes",
        "display_mean"      => "No",
        "display_units"     => "in"
    ],
    [
        "data_name_full"    => "Mean Daily Dew Point",
        "data_name_display" => "Dew Point",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Daily Min WC",
        "data_name_display" => "Min WC",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "No",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Daily Max HI",
        "data_name_display" => "Max HI",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "basic",
        "display_sum"       => "No",
        "display_mean"      => "No",
        "display_units"     => "°F"
    ],
    // WATER
    [
        "data_name_full"    => "Mean Water Temp",
        "data_name_display" => "Water Temp.",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "water",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Max Daily Water Temp",
        "data_name_display" => "Max Water Temp.",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "water",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Min Daily Water Temp",
        "data_name_display" => "Min Water Temp.",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "water",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Mean Daily Well Level",
        "data_name_display" => "Well Level",
        "conversion_type"   => "none",
        "precision_type"    => 2,
        "view_type"         => "numeric",
        "display_type"      => "water",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "ft"
    ],
    [
        "data_name_full"    => "Mean Daily Gage Height",
        "data_name_display" => "Gage Height",
        "conversion_type"   => "m_to_ft",
        "precision_type"    => 2,
        "view_type"         => "numeric",
        "display_type"      => "water",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "ft"
    ],
    [
        "data_name_full"    => "Max Daily Gage Height.",
        "data_name_display" => "Max Gage Height",
        "conversion_type"   => "m_to_ft",
        "precision_type"    => 2,
        "view_type"         => "numeric",
        "display_type"      => "water",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "ft"
    ],
    [
        "data_name_full"    => "Min Daily Gage Height.",
        "data_name_display" => "Min Gage Height",
        "conversion_type"   => "m_to_ft",
        "precision_type"    => 2,
        "view_type"         => "numeric",
        "display_type"      => "water",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "ft"
    ],
    // OTHER
    [
        "data_name_full"    => "Mean Daily Barometric Pressure",
        "data_name_display" => "Pressure",
        "conversion_type"   => "none",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "other",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "mb"
    ],
    [
        "data_name_full"    => "Mean Daily RH",
        "data_name_display" => "Mean RH",
        "conversion_type"   => "none",
        "precision_type"    => 0,
        "view_type"         => "numeric",
        "display_type"      => "other",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "%"
    ],
    [
        "data_name_full"    => "Heating Degree Days",
        "data_name_display" => "HDD",
        "conversion_type"   => "none",
        "precision_type"    => 0,
        "view_type"         => "numeric",
        "display_type"      => "other",
        "display_sum"       => "Yes",
        "display_mean"      => "No",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Cooling Degree Days",
        "data_name_display" => "CDD",
        "conversion_type"   => "none",
        "precision_type"    => 0,
        "view_type"         => "numeric",
        "display_type"      => "other",
        "display_sum"       => "Yes",
        "display_mean"      => "No",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Daily Solar",
        "data_name_display" => "Solar",
        "conversion_type"   => "none",
        "precision_type"    => 0,
        "view_type"         => "numeric",
        "display_type"      => "other",
        "display_sum"       => "Yes",
        "display_mean"      => "No",
        "display_units"     => "MJ"
    ],
    // AG
    [
        "data_name_full"    => "Daily Avg ST",
        "data_name_display" => "Avg ST",
        "conversion_type"   => "kelvin_to_fahrenheit",
        "precision_type"    => 1,
        "view_type"         => "numeric",
        "display_type"      => "ag",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "°F"
    ],
    [
        "data_name_full"    => "Daily Avg VWC",
        "data_name_display" => "Avg VWC",
        "conversion_type"   => "none",
        "precision_type"    => 3,
        "view_type"         => "numeric",
        "display_type"      => "ag",
        "display_sum"       => "No",
        "display_mean"      => "Yes",
        "display_units"     => "g/g"
    ],
    [
        "data_name_full"    => "Reference Evapotrans.",
        "data_name_display" => "Ref. ET",
        "conversion_type"   => "mm_to_inches",
        "precision_type"    => 2,
        "view_type"         => "numeric",
        "display_type"      => "ag",
        "display_sum"       => "Yes",
        "display_mean"      => "No",
        "display_units"     => "in"
    ],
    [
        "data_name_full"    => "GDD32F",
        "data_name_display" => "GDD (50°F)",
        "conversion_type"   => "gdd32F_50F",
        "precision_type"    => 0,
        "view_type"         => "numeric",
        "display_type"      => "ag",
        "display_sum"       => "Yes",
        "display_mean"      => "No",
        "display_units"     => "°F"
    ]
];

// Conversion functions
function convertToEnglishUnits($value, $conversionType) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            return ($value - 273.15) * 9/5 + 32;
        case 'ms_to_mph':
            return $value * 2.23694;
        case 'mm_to_inches':
            return $value * 0.0393701;
        case 'rad_to_degrees':
            return rad2deg($value);
        case 'gdd32F_50F':
            return max($value - 18, 0);
        case 'm_to_ft':
            return $value * 3.28084;
        case 'none':
        default:
            return $value;
    }
}

function getPrecision($precisionType) {
    return $precisionType;
}

function degreesToWindDirection($degrees) {
    $degrees = fmod($degrees, 360.0);
    if ($degrees < 0) {
        $degrees += 360.0;
    }
    $shiftedDegrees = $degrees + 11.25;
    if ($shiftedDegrees >= 360.0) {
        $shiftedDegrees -= 360.0;
    }
    $index = (int) floor($shiftedDegrees / 22.5);
    $directions = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW'];
    return $directions[$index];
}

function degreesToWindDirectionAvg($degrees) {
    return degreesToWindDirection($degrees);
}

function getUnitLabel($conversionType, $units) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            return $units;
        case 'ms_to_mph':
            return $units;
        case 'mm_to_inches':
            return "in";
        case 'rad_to_degrees':
            return "";
        case 'none':
        default:
            return $units;
    }
}

function getMetadataByDataType($name, $metadata) {
    foreach ($metadata as $meta) {
        if ($meta['data_name_full'] === $name) {
            return $meta;
        }
    }
    return null;
}

function getMetadataByDisplayName($displayName, $metadata) {
    foreach ($metadata as $meta) {
        if ($meta['data_name_display'] === $displayName) {
            return $meta;
        }
    }
    return null;
}

// Generate initial columns
$initialColumns = array_map(function($meta) {
    return $meta['data_name_display'];
}, $metadata);

$uniqueDataTypes = [];
$tableData       = [];
$columnData      = [];
$columnPrecision = [];
$flaggedData     = [];

// Process JSON data
if (isset($data['data']) && is_array($data['data'])) {
    foreach ($data['data'] as $dates) {
        foreach ($dates as $date => $metrics) {
            $formattedDate = explode(" ", $date)[0];
            $row = ['Date' => $formattedDate];
            foreach ($metrics as $info) {
                if (isset($info['FLAG'], $info['DATA_TYPE'], $info['VALUE'])) {
                    $metaInfo = getMetadataByDataType($info['DATA_TYPE'], $metadata);
                    if ($metaInfo) {
                        $dataTypeDisplay = $metaInfo['data_name_display'];
                        $conversionType  = $metaInfo['conversion_type'];
                        $precisionType   = $metaInfo['precision_type'];
                        $viewType        = $metaInfo['view_type'];
                        $units           = $info['UNITS'];
                        $unitLabel = getUnitLabel($conversionType, $units);
                        $uniqueDataTypes[$dataTypeDisplay] = $unitLabel;
                        $value     = $info['VALUE'];
                        $precision = getPrecision($precisionType);
                        $columnPrecision[$dataTypeDisplay] = $precision;

                        if ($info['FLAG'] == '1' || $info['FLAG'] == '7') {
                            $convertedValue = convertToEnglishUnits($value, $conversionType);
                            if ($viewType === 'text') {
                                $row[$dataTypeDisplay] = degreesToWindDirection($convertedValue);
                                $row[$dataTypeDisplay . '_degrees'] = $convertedValue;
                            } else {
                                if ($conversionType === 'kelvin_to_fahrenheit' || $conversionType === 'ms_to_mph') {
                                    $convertedValue = round($convertedValue, 1);
                                    $row[$dataTypeDisplay] = number_format($convertedValue, 1, '.', '');
                                } else {
                                    $row[$dataTypeDisplay] = number_format($convertedValue, $precision, '.', '');
                                }
                            }
                            if ($info['FLAG'] == '7') {
                                $flaggedData[$formattedDate][$dataTypeDisplay] = 'CLIMATE';
                            }
                        } else {
                            $row[$dataTypeDisplay] = '--';
                        }
                    } else {
                        $row[$info['DATA_TYPE']] = '--';
                    }
                }
            }
            $tableData[] = $row;
        }
    }
}

// Columns array
$columns = array_merge(['Date'], $initialColumns);

// Initialize columnData for summary calculations
foreach ($columns as $colName) {
    if ($colName != 'Date') {
        $columnData[$colName] = [];
    }
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Tag Manager / GA snippet -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-BTMTESR1DZ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-BTMTESR1DZ');
    </script>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery and DataTables scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="scripts.js"></script>
    <title>Responsive Data Table</title>
</head>
<body>
    <!-- Banner -->
    <div class="summary-banner-container">
        <div class="banner-title">Monthly Summary</div>
        <div class="collapsed-banner">
            <span>📢 New: DEOS Monthly Summaries! Now with CSV downloads, improved hydrologic stats, and better data quality. </span>
            <a href="#" id="toggleBanner">[Click to Learn More]</a>
            <div class="expanded-banner" style="display:none;">
                <p>Welcome to the new DEOS Monthly Summaries page!</p>
                <p>We’ve made major improvements, including:</p>
                <ul>
                    <li>✅ CSV downloads for easy data access</li>
                    <li>✅ Monthly statistics for DEOS hydrologic parameters</li>
                    <li>✅ Improved quality control of the underlying data</li>
                </ul>
                <p>We’re still updating our historical climate stats, so data is currently available from 2015 onward. The full dataset (back to 2004) should be available later this summer.</p>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('toggleBanner').addEventListener('click', function(e) {
            e.preventDefault();
            var expanded = document.querySelector('.expanded-banner');
            if(expanded.style.display === 'none'){
                expanded.style.display = 'block';
                this.textContent = '[Show Less]';
            } else {
                expanded.style.display = 'none';
                this.textContent = '[Click to Learn More]';
            }
        });
    </script>

    <!-- Top Controls -->
    <input hidden type="text" id="station" value="<?php echo htmlspecialchars($station_name); ?>">
    <div class="top-controls">
        <div class="left-controls">
            <div class="select-wrapper">
                <select id="month" class="custom-select" onchange="refreshWithNewParams()">
                    <?php
                    $months = [
                        "JAN" => "January", "FEB" => "February", "MAR" => "March", "APR" => "April",
                        "MAY" => "May", "JUN" => "June", "JUL" => "July", "AUG" => "August",
                        "SEP" => "September", "OCT" => "October", "NOV" => "November", "DEC" => "December"
                    ];
                    foreach($months as $code => $name) {
                        $selected = ($code === strtoupper($month)) ? " selected" : "";
                        echo "<option value='$code'$selected>$name</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="select-wrapper">
                <select id="year" class="custom-select" onchange="refreshWithNewParams()"></select>
                <script>
                    const yearDropdown = document.getElementById('year');
                    const startYear = 2004;
                    const currentYear = new Date().getFullYear();
                    const selectedYear = '<?php echo $year; ?>';
                    for (let y = currentYear; y >= startYear; y--) {
                        const option = document.createElement('option');
                        option.value = y;
                        option.textContent = y;
                        if (y.toString() === selectedYear) {
                            option.selected = true;
                        }
                        yearDropdown.appendChild(option);
                    }
                </script>
            </div>
        </div>
        <div class="center-controls">
            <div class="select-wrapper station-wrapper">
                <select id="station-select" class="custom-select" onchange="refreshWithNewParams()">
                    <option value="">--Select a station--</option>
                </select>
            </div>
        </div>
        <div class="right-controls">
            <div class="toggle-buttons">
                <div class="dropdown-view">
                    <select id="viewSelect" class="custom-select">
                        <option value="basic" selected>Basic</option>
                        <option value="other">Other</option>
                        <option value="water">Water</option>
                        <option value="ag">Ag Wx</option>
                    </select>
                    <i class="fa fa-caret-down"></i>
                </div>
                <button id="saveCsvButton" class="save-button" title="Download CSV"><i class="fa fa-download"></i></button>
                <button id="infoButton" class="info-button" title="Information"><i class="fa fa-info-circle"></i></button>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <?php if (empty($tableData)): ?>
            <table id="dataTable" class="display nowrap" style="width:100%">
                <tr>
                    <td style="text-align:center; padding:20px;">No Data Available</td>
                </tr>
            </table>
        <?php else: ?>
            <table id="dataTable" class="display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <?php foreach ($columns as $colName): ?>
                            <th><?php echo $colName; ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($columns as $colName): ?>
                            <?php if ($colName === "Date"): ?>
                                <th></th>
                            <?php else: 
                                $meta = getMetadataByDisplayName($colName, $metadata);
                                $display_units = ($meta && isset($meta['display_units']) && $meta['display_units'] !== "") ? $meta['display_units'] : '';
                            ?>
                                <th><?php echo $display_units; ?></th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tableData)): ?>
                        <tr>
                            <td colspan="<?php echo count($columns); ?>" style="text-align:center;">No Data Available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tableData as $row): ?>
                            <tr>
                                <?php foreach ($columns as $colName): ?>
                                    <?php 
                                        $cellValue = isset($row[$colName]) ? $row[$colName] : '--';
                                        $highlightClass = '';
                                        if (isset($flaggedData[$row['Date']][$colName]) && $flaggedData[$row['Date']][$colName] === 'CLIMATE') {
                                            $highlightClass = 'climate-flag';
                                        }
                                    ?>
                                    <td class="<?php echo $highlightClass; ?>"><?php echo $cellValue; ?></td>
                                    <?php
                                        if ($colName != 'Date') {
                                            $metaInfo = getMetadataByDisplayName($colName, $metadata);
                                            if ($metaInfo) {
                                                $viewType = $metaInfo['view_type'];
                                                if ($viewType === 'numeric') {
                                                    $columnData[$colName][] = is_numeric($cellValue) ? $cellValue : '--';
                                                } elseif ($viewType === 'text') {
                                                    $originalDegrees = $row[$colName . '_degrees'] ?? '--';
                                                    $columnData[$colName][] = is_numeric($originalDegrees) ? $originalDegrees : '--';
                                                }
                                            } else {
                                                $columnData[$colName][] = '--';
                                            }
                                        }
                                    ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php
                // Calculate summary rows
                $summarySumRow  = [];
                $summaryMeanRow = [];
                foreach ($columns as $colName) {
                    if ($colName == 'Date') {
                        $summarySumRow[$colName]  = 'Total';
                        $summaryMeanRow[$colName] = 'Mean';
                    } else {
                        $metaInfo     = getMetadataByDisplayName($colName, $metadata);
                        if ($metaInfo) {
                            $precision = ($metaInfo['conversion_type'] === 'kelvin_to_fahrenheit' || $metaInfo['conversion_type'] === 'ms_to_mph')
                                         ? 1 : getPrecision($metaInfo['precision_type']);
                            $viewType    = $metaInfo['view_type'];
                            $values      = $columnData[$colName];
                            $displaySum  = isset($metaInfo['display_sum']) && $metaInfo['display_sum'] === 'Yes';
                            $displayMean = isset($metaInfo['display_mean']) && $metaInfo['display_mean'] === 'Yes';
                            if ($viewType === 'numeric') {
                                $validValues = array_filter($values, 'is_numeric');
                                if (count($validValues) !== count($values)) {
                                    $validValues = null;
                                }
                                if (!empty($validValues)) {
                                    $summarySumRow[$colName] = $displaySum ? number_format(array_sum($validValues), $precision) : '--';
                                    $mean = array_sum($validValues) / count($validValues);
                                    $summaryMeanRow[$colName] = $displayMean ? number_format($mean, $precision, '.', '') : '--';
                                } else {
                                    $summarySumRow[$colName]  = '--';
                                    $summaryMeanRow[$colName] = '--';
                                }
                            } elseif ($viewType === 'text') {
                                if ($displayMean) {
                                    $validDegrees = array_filter($values, 'is_numeric');
                                    if (!empty($validDegrees)) {
                                        $sumSin = 0;
                                        $sumCos = 0;
                                        foreach ($validDegrees as $deg) {
                                            $rad = deg2rad($deg);
                                            $sumSin += sin($rad);
                                            $sumCos += cos($rad);
                                        }
                                        $avgRad = atan2($sumSin, $sumCos);
                                        $avgDeg = rad2deg($avgRad);
                                        if ($avgDeg < 0) {
                                            $avgDeg += 360;
                                        }
                                        $summaryMeanRow[$colName] = degreesToWindDirectionAvg($avgDeg);
                                    } else {
                                        $summaryMeanRow[$colName] = '--';
                                    }
                                } else {
                                    $summaryMeanRow[$colName] = '--';
                                }
                                $summarySumRow[$colName] = '--';
                            } else {
                                $summarySumRow[$colName]  = '--';
                                $summaryMeanRow[$colName] = '--';
                            }
                        } else {
                            $summarySumRow[$colName]  = '--';
                            $summaryMeanRow[$colName] = '--';
                        }
                    }
                }
                ?>
                <tfoot>
                    <tr>
                        <?php foreach ($columns as $colName): ?>
                            <td><b><?php echo $summarySumRow[$colName]; ?></b></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($columns as $colName): ?>
                            <td><b><?php echo $summaryMeanRow[$colName]; ?></b></td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>

    <!-- Embed metadata for JavaScript -->
    <script id="metadata" type="application/json"><?php echo json_encode($metadata); ?></script>

    <!-- Info Popup -->
    <div id="infoPopup" class="info-popup">
        <div class="info-popup-content">
            <h3>Monthly Summary Key</h3>
            <ul>
                <li><span style="color:red;">Red</span>: Indicates the highest daily value of the month.</li>
                <li><span style="color:blue;">Blue</span>: Indicates the lowest daily value of the month.</li>
                <li><span style="color:green;">Green</span>: Indicates the largest daily sum or value for the month.</li>
                <li><span style="background-color: lightblue;">Blue Shading</span>: Highlights a multi-day rainfall total. While the daily values within this period may not be individually valid, their combined total is accurate.</li>
                <li><b>'--'</b>: Denotes a missing value due to an insufficient number of valid readings for that day or for the month overall.</li>
                <li>🚫: The station was installed after the selected time.</li>
            </ul>
            <button id="closeInfoPopup" class="close-popup">Close</button>
        </div>
    </div>
</body>
</html>
<?php include('footer.php'); ?>
