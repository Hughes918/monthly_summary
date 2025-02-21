<?php
header('Content-Type: text/html; charset=UTF-8');

// Retrieve query parameters
$queryString = $_SERVER['QUERY_STRING'] ?? (isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) : null);

if (!empty($queryString)) {
    parse_str($queryString, $queryParams);
    $station_name = !empty($queryParams['station']) ? htmlspecialchars($queryParams['station']) : 'DWWK';
    $date = !empty($queryParams['date']) ? htmlspecialchars($queryParams['date']) : 'JUN_2023';
} else {
    $station_name = 'DWWK';
    $date = 'JUN_2023';
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
        $jsonFile = "http://150.136.239.199/almanac/retrieve.old.php?start=$start_date&end=$end_date&station_name=$station_name";
       
    } else {
        die("Invalid month or year format provided.<br>");
    }
} else {
    die("Date must be in the format 'MMM_YYYY', e.g., 'JUN_2023'.<br>");
}

$jsonData = file_get_contents($jsonFile);
$data     = json_decode($jsonData, true);
if ($data === null) {
    echo "<p>Error decoding JSON data. Please check the data format.</p>";
    exit;
}

/**
 * Metadata array with conditional sum/mean
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
        "display_mean"      => "No",
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
        "display_mean"      => "No",
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
        "display_units"     => "inHg"
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
        "display_units"     => "kWh/m²"
    ],
    

    // AG
    // Note: removed second 'Gage Precipitation (Daily)' for 'ag' to avoid duplicating precipitation
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
        "display_units"     => "%" // or appropriate unit of volumetric water content
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
        "data_name_display" => "GDD (50°F)", //data converted to 50°F base
        "conversion_type"   => "gdd32F_50F",
        "precision_type"    => 0,
        "view_type"         => "numeric",
        "display_type"      => "ag",
        "display_sum"       => "Yes",
        "display_mean"      => "No",
        "display_units"     => "°F"
    ]
];

// Conversion function
function convertToEnglishUnits($value, $conversionType) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            // Values are already in Fahrenheit; no conversion needed.
            return $value;
        case 'ms_to_mph':
            // Removed conversion: simply return the value unchanged.
            return $value;
        case 'mm_to_inches':
            return $value * 0.0393701;
        case 'rad_to_degrees':
            return rad2deg($value);
        case 'gdd32F_50F':
            // Adjust as necessary; this example retains similar logic.
            return max($value - 18, 0);
        case 'none':
        default:
            return $value;
    }
}

// Precision function
function getPrecision($precisionType) {
    return $precisionType;
}

// Convert degrees to wind direction
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
    $directions = [
        'N','NNE','NE','ENE','E','ESE','SE','SSE',
        'S','SSW','SW','WSW','W','WNW','NW','NNW'
    ];
    return $directions[$index];
}

// We'll convert a final average angle to a cardinal direction
function degreesToWindDirectionAvg($degrees) {
    return degreesToWindDirection($degrees);
}

// Return label for units
function getUnitLabel($conversionType, $units) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            // Return units unmodified since values are already in °F.
            return $units;
        case 'ms_to_mph':
            // Optionally, simply return the provided units.
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

// Process data from JSON
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

                        // Check flags
                        if ($info['FLAG'] == '1' || $info['FLAG'] == '7') {
                            $convertedValue = convertToEnglishUnits($value, $conversionType);

                            if ($viewType === 'text') {
                                // Convert numeric angle to cardinal direction
                                $row[$dataTypeDisplay] = degreesToWindDirection($convertedValue);
                                $row[$dataTypeDisplay . '_degrees'] = $convertedValue;
                            } else {
                                // Force 1 decimal point for temperature and wind conversions.
                                if ($conversionType === 'kelvin_to_fahrenheit' || $conversionType === 'ms_to_mph') {
                                    $convertedValue = round($convertedValue, 1);
                                    $row[$dataTypeDisplay] = number_format($convertedValue, 1, '.', '');
                                } else {
                                    $row[$dataTypeDisplay] = number_format($convertedValue, $precision, '.', '');
                                }
                            }

                            // Mark climate data if FLAG == '7'
                            if ($info['FLAG'] == '7') {
                                $flaggedData[$formattedDate][$dataTypeDisplay] = 'CLIMATE';
                            }
                        } else {
                            // For non-1/7 flags, no data
                            $row[$dataTypeDisplay] = '--';
                        }
                    } else {
                        // No metadata for this data_type
                        $row[$info['DATA_TYPE']] = '--';
                    }
                }
            }
            $tableData[] = $row;
        }
    }
}

// Columns
$columns = array_merge(['Date'], $initialColumns);

// Initialize columnData for summary calculations
foreach ($columns as $colName) {
    if ($colName != 'Date') {
        $columnData[$colName] = [];
    }
}

// Include the header, which outputs the DOCTYPE, HTML, head, and opening body tags.
include('header.php');

// Build HTML table
echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n"; // Add viewport meta tag
echo "    <title>Responsive Data Table</title>\n";
echo "    <!-- DataTables CSS -->\n";
echo "    <link rel='stylesheet' href='https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css'>\n";
echo "    <link rel='stylesheet' href='https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css'>\n";
echo "    <link rel='stylesheet' href='styles.css'>\n\n";
echo "    <!-- Updated Font Awesome CSS for Save Icon -->\n";
echo "    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>\n";
echo "    <!-- jQuery -->\n";
echo "    <script src='https://code.jquery.com/jquery-3.5.1.js'></script>\n";
echo "    <!-- DataTables JS -->\n";
echo "    <script src='https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js'></script>\n";
echo "    <script src='https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js'></script>\n";
echo "    <script src='https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js'></script>\n";
echo "    <script src='https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js'></script>\n";
echo "    <script src='scripts.js'></script>\n";
echo "</head>\n";
echo "<body>\n";

echo "    <!-- Dropdowns for Month and Year Selection -->\n";
echo "    <input hidden type='text' id='station' value='" . htmlspecialchars($station_name) . "'>\n\n";

// Wrap each select in a div for additional styling if needed
echo "<div class='top-controls'>\n";
echo "    <div class='left-controls'>\n";
echo "        <div class='select-wrapper'>\n";
echo "            <select id='month' class='custom-select' onchange='refreshWithNewParams()'>\n";

$months = [
    "JAN"=>"January","FEB"=>"February","MAR"=>"March","APR"=>"April","MAY"=>"May","JUN"=>"June",
    "JUL"=>"July","AUG"=>"August","SEP"=>"September","OCT"=>"October","NOV"=>"November","DEC"=>"December"
];

foreach($months as $code => $name) {
    echo "<option value='$code'" . ($code === strtoupper($month) ? " selected" : "") . ">$name</option>\n";
}
echo "            </select>\n";
echo "        </div>\n";

echo "        <div class='select-wrapper'>\n";
echo "            <select id='year' class='custom-select' onchange='refreshWithNewParams()'>\n";
echo "            <script>\n";
echo "                const yearDropdown = document.getElementById('year');\n";
echo "                const startYear = 2004;\n";
echo "                const currentYear = new Date().getFullYear();\n";
echo "                const selectedYear = '" . $year . "';\n";
echo "                for (let y = currentYear; y >= startYear; y--) {\n";
echo "                    const option = document.createElement('option');\n";
echo "                    option.value = y;\n";
echo "                    option.textContent = y;\n";
echo "                    if (y.toString() === selectedYear) {\n";
echo "                       option.selected = true;\n";
echo "                    }\n";
echo "                    yearDropdown.appendChild(option);\n";
echo "                }\n";
echo "            </script>\n";
echo "            </select>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "    <div class='center-controls'>\n";
echo "        <div class='select-wrapper station-wrapper'>\n";
echo "            <select id='station-select' class='custom-select' onchange='refreshWithNewParams()'>\n";
echo "                <option value='' >--Select a station--</option>\n";
echo "            </select>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "    <div class='right-controls'>\n";
echo "        <div class='toggle-buttons'>\n";
echo "            <div class='dropdown-view'>\n";
echo "                <select id='viewSelect' class='custom-select'>\n";
echo "                    <option value='basic' selected>Basic</option>\n";
echo "                    <option value='other'>Other</option>\n";
echo "                    <option value='water'>Water</option>\n";
echo "                    <option value='ag'>Ag Wx</option>\n";
echo "                </select>\n";
echo "                <i class='fa fa-caret-down'></i>\n";
echo "            </div>\n";
echo "            <button id='saveCsvButton' class='save-button' title='Download CSV'><i class='fa fa-download'></i></button>\n";
echo "        </div>\n";
echo "    </div>\n";
echo "</div>\n";

echo "<div class='table-container'>\n"; // Add scrollable container for the entire table
echo "<table id='dataTable' class='display nowrap' style='width:100%'>\n";
echo "<thead>\n";
echo "<tr>";
foreach ($columns as $colName) {
    echo "<th>$colName</th>";
}
echo "</tr><tr>";
foreach ($columns as $colName) {
    if ($colName === "Date") {
        echo "<th></th>";
    } else {
        $meta = getMetadataByDisplayName($colName, $metadata);
        $display_units = ($meta && isset($meta['display_units']) && $meta['display_units'] !== "") ? $meta['display_units'] : '';
        echo "<th>$display_units</th>";
    }
}
echo "</tr></thead>\n";
echo "<tbody>\n";

// Table data rows
foreach ($tableData as $row) {
    echo "<tr>";
    foreach ($columns as $colName) {
        $cellValue = isset($row[$colName]) ? $row[$colName] : '--';
        // If climate data flagged, highlight with a CSS class (not bold)
        $highlightClass = '';
        if (isset($flaggedData[$row['Date']][$colName]) && $flaggedData[$row['Date']][$colName] === 'CLIMATE') {
            $highlightClass = 'climate-flag';
        }

        echo "<td class='$highlightClass'>$cellValue</td>";

        // Store data for summaries
        if ($colName != 'Date') {
            $metaInfo = getMetadataByDisplayName($colName, $metadata);
            if ($metaInfo) {
                $viewType = $metaInfo['view_type'];
                if ($viewType === 'numeric') {
                    $columnData[$colName][] = is_numeric($cellValue) ? $cellValue : '--';
                } elseif ($viewType === 'text') {
                    // For wind direction, store numeric degrees if available
                    $originalDegrees = $row[$colName . '_degrees'] ?? '--';
                    $columnData[$colName][] = is_numeric($originalDegrees) ? $originalDegrees : '--';
                }
            } else {
                $columnData[$colName][] = '--';
            }
        }
    }
    echo "</tr>";
}

echo "</tbody>\n";

// Calculate summary rows
$summarySumRow  = [];
$summaryMeanRow = [];

// We'll do a final function that helps compute average direction if needed
foreach ($columns as $colName) {
    if ($colName == 'Date') {
        // We'll store the label but wrap in <b> for the final rendering
        $summarySumRow[$colName]  = 'Total';
        $summaryMeanRow[$colName] = 'Mean';
    } else {
        $metaInfo     = getMetadataByDisplayName($colName, $metadata);
        if ($metaInfo) {
            // Force precision of 1 if conversion_type is for temperature or wind.
            if ($metaInfo['conversion_type'] === 'kelvin_to_fahrenheit' || $metaInfo['conversion_type'] === 'ms_to_mph') {
                $precision = 1;
            } else {
                $precision = getPrecision($metaInfo['precision_type']);
            }
            $viewType    = $metaInfo['view_type'];
            $values      = $columnData[$colName];
            $displaySum  = isset($metaInfo['display_sum'])  && $metaInfo['display_sum']  === 'Yes';
            $displayMean = isset($metaInfo['display_mean']) && $metaInfo['display_mean'] === 'Yes';

            if ($viewType === 'numeric') {
                // Filter numeric data
                $validValues = array_filter($values, 'is_numeric');
                if (count($validValues) !== count($values)) {
                    $validValues = null;
                }
                if (!empty($validValues)) {
                    // Summation
                    if ($displaySum) {
                        $summarySumRow[$colName] = number_format(array_sum($validValues), $precision);
                    } else {
                        $summarySumRow[$colName] = '--';
                    }
                    // Mean
                    if ($displayMean) {
                        $mean = array_sum($validValues) / count($validValues);
                        $summaryMeanRow[$colName] = number_format($mean, $precision, '.', '');
                    } else {
                        $summaryMeanRow[$colName] = '--';
                    }
                } else {
                    // No valid numeric entries
                    $summarySumRow[$colName]  = '--';
                    $summaryMeanRow[$colName] = '--';
                }
            } elseif ($viewType === 'text') {
                // Potentially do mean wind direction if $displayMean is Yes
                if ($displayMean) {
                    // We have stored numeric degrees in $values if they exist
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
                        // Convert average angle to cardinal direction
                        $summaryMeanRow[$colName] = degreesToWindDirectionAvg($avgDeg);
                    } else {
                        $summaryMeanRow[$colName] = '--';
                    }
                } else {
                    $summaryMeanRow[$colName] = '--';
                }
                // For sum, we do not sum directions
                $summarySumRow[$colName] = '--';
            } else {
                // For any other view type, skip summary
                $summarySumRow[$colName]  = '--';
                $summaryMeanRow[$colName] = '--';
            }
        } else {
            // No metadata
            $summarySumRow[$colName]  = '--';
            $summaryMeanRow[$colName] = '--';
        }
    }
}

echo "<tfoot>\n";

// Render the sum row, all bold
echo "<tr>";
foreach ($columns as $colName) {
    echo "<td><b>" . $summarySumRow[$colName] . "</b></td>";
}
echo "</tr>\n";

// Render the mean row, all bold
echo "<tr>";
foreach ($columns as $colName) {
    echo "<td><b>" . $summaryMeanRow[$colName] . "</b></td>";
}
echo "</tr>\n";

echo "</tfoot>\n";
echo "</table>\n";
echo "</div>\n"; // Close scrollable container

// Embed metadata for JavaScript
echo "<script id='metadata' type='application/json'>" . json_encode($metadata) . "</script>\n";

echo "</body></html>\n";

include('footer.php');
?>
