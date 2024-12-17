<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Responsive Data Table</title>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    
    <style>
        /* Ensure uniform table layout */
        table.dataTable {
            width: 100%;
            table-layout: auto;
        }

        /* Ensure the first column is sticky */
table.dataTable tbody tr td:first-child,
table.dataTable thead tr th:first-child {
    position: sticky;
    left: 0;
    z-index: 2; /* Ensure the header stays above other rows */
    background-color: #ffffff; /* Match the table background */
    box-shadow: 1px 0 2px rgba(0, 0, 0, 0.1); /* Optional shadow for distinction */
}

        table.dataTable th, table.dataTable td {
            max-width: 175px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Remove all spacing and borders between header rows */
        table.dataTable thead th {
            border-bottom: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        table.dataTable thead tr:nth-child(2) th {
            border-top: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Save button styling */
        #saveCsvButton {
            margin-top: 15px;
            display: block;
            width: 150px;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            text-align: center;
            font-size: 14px;
            text-decoration: none;
            border-radius: 5px;
        }

        #saveCsvButton:hover {
            background-color: #0056b3;
        }

        /* Hide the default DataTables button container */
        .dt-buttons {
            display: none;
        }

        .climate-flag {
    background-color: #e0f7fa !important; /* Very light blue */
        }
        
    table.dataTable thead .sorting,
table.dataTable thead .sorting_asc,
table.dataTable thead .sorting_desc {
    background-image: none !important; /* Remove the sorting arrow */
    cursor: default; /* Change the cursor to default (non-clickable) */
}

/* Styling for the last row in the table */
#dataTable tbody tr:last-child {
    background-color: #f8f9fa; /* Light gray background */
    font-weight: bold;         /* Bold font */
    border-top: 2px solid #dee2e6; /* Top border for separation */
}
.toggle-buttons {
            margin: 15px 0;
        }

        .toggle-buttons button {
            margin-right: 10px;
            padding: 8px 16px;
            border: none;
            background-color: #007BFF;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .toggle-buttons button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<!-- Buttons for Display Type -->
<div class="toggle-buttons">
    <button class="toggle-display" data-display="basic">Basic</button>
    <button class="toggle-display" data-display="water">Water</button>
    <button class="toggle-display" data-display="other">Other</button>
    <button class="toggle-display" data-display="ag">Ag</button>


<!-- Dropdowns for Month and Year Selection -->

<label for="station">Station:</label>
    <input type="text" id="station" value="DWWK">

    <label for="month">Month:</label>
    <select id="month">
        <option value="JAN">January</option>
        <option value="FEB">February</option>
        <option value="MAR">March</option>
        <option value="APR">April</option>
        <option value="MAY">May</option>
        <option value="JUN">June</option>
        <option value="JUL">July</option>
        <option value="AUG">August</option>
        <option value="SEP">September</option>
        <option value="OCT">October</option>
        <option value="NOV">November</option>
        <option value="DEC">December</option>
    </select>

    <label for="year">Year:</label>
    <select id="year">
        <script>
            const yearDropdown = document.getElementById('year');
            const startYear = 2020;
            const currentYear = new Date().getFullYear();
            for (let year = currentYear; year >= startYear; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearDropdown.appendChild(option);
            }
        </script>
    </select>

    <button id="submitButton" onclick="refreshWithNewParams()">Submit</button>
</div>


<?php

// Check if QUERY_STRING is set and use REQUEST_URI as a fallback if available
$queryString = $_SERVER['QUERY_STRING'] ?? (isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) : null);

// Ensure the query string is not null or empty before parsing
if (!empty($queryString)) {
    // Parse the query string into an associative array
    parse_str($queryString, $queryParams);

    // Validate required parameters or use defaults
    $station_name = !empty($queryParams['station']) ? htmlspecialchars($queryParams['station']) : 'DWWK';
    $date = !empty($queryParams['date']) ? htmlspecialchars($queryParams['date']) : 'JUN_2023';
} else {
    // Fallback to default values if query string is empty
    $station_name = 'DWWK';
    $date = 'JUN_2023';
}

// Convert date from 'MAY_2023' to '2023-05-01' and '2023-05-31'
if (strpos($date, '_') !== false) {
    list($month, $year) = explode('_', $date);
    $dateObj = DateTime::createFromFormat('M', ucfirst(strtolower($month))); // Normalize case for the month

    if ($dateObj && is_numeric($year)) {
        $monthNumber = $dateObj->format('m');
        $start_date = "$year-$monthNumber-01";
        $end_date = date("Y-m-t", strtotime($start_date)); // Last day of the month

        // Create the new $jsonFile URL
        $jsonFile = "http://150.136.239.199/almanac/retrieve.old.php?start=$start_date&end=$end_date&station_name=$station_name";

    } else {
        die("Invalid month or year format provided.<br>");
    }
} else {
    die("Date must be in the format 'MMM_YYYY', e.g., 'JUN_2023'.<br>");
}

// Path to the local JSON file
//$jsonFile = 'http://150.136.239.199/almanac/retrieve.old.php?start=2023-03-01&end=2023-03-31&station_id=2312';

// Metadata array
$metadata = [
    ["data_name_full" => "Mean Daily Temp.", "data_name_display" => "Mean Temp.", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Max Daily Temp.", "data_name_display" => "Max Temp.", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Min Daily Temp.", "data_name_display" => "Min Temp.", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Mean Wind Speed", "data_name_display" => "Wind Speed", "conversion_type" => "ms_to_mph", "precision_type" => 0, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Mean Wind Direction", "data_name_display" => "Wind Dir.", "conversion_type" => "rad_to_degrees", "precision_type" => 0, "view_type" => "text", "display_type" => "basic"],
    ["data_name_full" => "Peak Wind Gust Speed (Daily)", "data_name_display" => "Max Gust", "conversion_type" => "ms_to_mph", "precision_type" => 0, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Gage Precipitation (Daily)", "data_name_display" => "Precip", "conversion_type" => "mm_to_inches", "precision_type" => 2, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Mean Daily Dew Point", "data_name_display" => "Dew Point", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Daily Min WC", "data_name_display" => "Min WC", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Daily Max HI", "data_name_display" => "Max HI", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
   
    ["data_name_full" => "Mean Water Temp", "data_name_display" => "Water Temp.", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "water"],
    ["data_name_full" => "Max Daily Water Temp", "data_name_display" => "Max Water Temp.", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "water"],
    ["data_name_full" => "Min Daily Water Temp", "data_name_display" => "Min Water Temp.", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "water"],
    ["data_name_full" => "Mean Daily Well Level", "data_name_display" => "Well Level", "conversion_type" => "none", "precision_type" => 2, "view_type" => "numeric", "display_type" => "water"],
 
    ["data_name_full" => "Daily Max RH", "data_name_display" => "Max RH", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Daily Min RH", "data_name_display" => "Min RH", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Daily Max ST", "data_name_display" => "Max ST", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Daily Min ST", "data_name_display" => "Min ST", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Heating Degree Days", "data_name_display" => "HDD", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Cooling Degree Days", "data_name_display" => "CDD", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],
    
    ["data_name_full" => "Mean Daily RH", "data_name_display" => "Mean RH", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"],
       ["data_name_full" => "Daily Avg ST", "data_name_display" => "Avg ST", "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "Daily Solar", "data_name_display" => "Solar", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "Reference Evapotrans.", "data_name_display" => "Ref. ET", "conversion_type" => "mm_to_inches", "precision_type" => 2, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "GDD0C", "data_name_display" => "GDD (0°C)", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "GDD32F", "data_name_display" => "GDD (32°F)", "conversion_type" => "none", "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"]

];


// Check if the file exists and read data
//if (!file_exists($jsonFile)) {
//    echo "<p>Error: JSON file not found!</p>";
//    exit;
//}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);
if ($data === null) {
    echo "<p>Error decoding JSON data. Please check the data format.</p>";
    exit;
}

// Conversion function for English units
function convertToEnglishUnits($value, $conversionType) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            return ($value - 273.15) * 9 / 5 + 32;
        case 'ms_to_mph':
            return $value * 2.23694;
        case 'mm_to_inches':
            return $value * 0.0393701;
        case 'rad_to_degrees':
            return rad2deg($value);
        case 'none':
        default:
            return $value;
    }
}

// Function to get precision based on precision_type
function getPrecision($precisionType) {
    return $precisionType;
}

// Convert degrees to wind directions
function degreesToWindDirection($degrees) {
    // Normalize degrees to be within [0, 360)
    $degrees = fmod($degrees, 360.0);
    if ($degrees < 0) {
        $degrees += 360.0;
    }

    // Adjust degrees by adding 11.25 to align with compass sectors
    $shiftedDegrees = $degrees + 11.25;
    if ($shiftedDegrees >= 360.0) {
        $shiftedDegrees -= 360.0;
    }

    // Calculate the index by dividing by 22.5 degrees per sector
    $index = (int) floor($shiftedDegrees / 22.5);

    $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE',
                   'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];

    return $directions[$index];
}

// Get unit labels
function getUnitLabel($conversionType, $units) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            return "°F";
        case 'ms_to_mph':
            return "mph";
        case 'mm_to_inches':
            return "in";
        case 'rad_to_degrees':
            return ""; // No unit label for wind direction
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

// Generate initial columns from metadata
$initialColumns = array_map(function($meta) {
    return $meta['data_name_display'];
}, $metadata);

// Initialize variables
$uniqueDataTypes = [];
$tableData = [];
$columnData = []; // For summary calculations
$columnPrecision = []; // To store precision per column

// Initialize a structure to track flags
$flaggedData = []; // To store "climate" flagged data

// Process data
if (isset($data['data']) && is_array($data['data'])) {
    foreach ($data['data'] as $dates) {
        foreach ($dates as $date => $metrics) {
            $formattedDate = explode(" ", $date)[0];
            $row = ['Date' => $formattedDate];
            foreach ($metrics as $info) {
                if (isset($info['FLAG']) && isset($info['DATA_TYPE']) && isset($info['VALUE'])) {
                    $metaInfo = getMetadataByDataType($info['DATA_TYPE'], $metadata);
                    if ($metaInfo) {
                        $dataTypeDisplay = $metaInfo['data_name_display'];
                        $conversionType = $metaInfo['conversion_type'];
                        $precisionType = $metaInfo['precision_type'];
                        $viewType = $metaInfo['view_type'];

                        $units = $info['UNITS'];
                        $unitLabel = getUnitLabel($conversionType, $units);
                        $uniqueDataTypes[$dataTypeDisplay] = $unitLabel;

                        $value = $info['VALUE'];
                        $precision = getPrecision($precisionType);
                        $columnPrecision[$dataTypeDisplay] = $precision;

                        // Handle flags and view types as needed
                        if ($info['FLAG'] == '1' || $info['FLAG'] == '7') {
                            $convertedValue = convertToEnglishUnits($value, $conversionType);

                            if ($viewType === 'text') {
                                // For wind direction, convert degrees to direction
                                $row[$dataTypeDisplay] = degreesToWindDirection($convertedValue);
                                // Store the degree value for averaging
                                $row[$dataTypeDisplay . '_degrees'] = $convertedValue;
                            } else { // numeric
                                $row[$dataTypeDisplay] = round($convertedValue, $precision);
                            }

                            // Mark as 'CLIMATE' if flag is '7'
                            if ($info['FLAG'] == '7') {
                                $flaggedData[$formattedDate][$dataTypeDisplay] = 'CLIMATE';
                            }
                        } else {
                            // For other flags, set 'N/A'
                            $row[$dataTypeDisplay] = 'N/A';
                        }
                    } else {
                        // Metadata not found, handle accordingly
                        $row[$info['DATA_TYPE']] = 'N/A';
                    }
                }
            }
            $tableData[] = $row;
        }
    }
}

// Build columns
$columns = array_merge(['Date'], $initialColumns);

// Initialize $columnData array
foreach ($columns as $colName) {
    if ($colName != 'Date') {
        $columnData[$colName] = [];
    }
}

// Build HTML table
echo "<table id='dataTable' class='display nowrap' style='width:100%'>";
echo "<thead>";
// First row: Column names
echo "<tr>";
foreach ($columns as $colName) {
    echo "<th>$colName</th>";
}
echo "</tr>";
// Second row: Units
echo "<tr>";
foreach ($columns as $colName) {
    echo "<th>" . ($colName === "Date" ? "" : ($uniqueDataTypes[$colName] ?? '')) . "</th>";
}
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach ($tableData as $row) {
    echo "<tr>";
    foreach ($columns as $colName) {
        $cellValue = isset($row[$colName]) ? $row[$colName] : 'N/A';
        // Check if this cell is flagged as "CLIMATE"
        $highlightClass = '';
        if (isset($flaggedData[$row['Date']][$colName]) && $flaggedData[$row['Date']][$colName] === 'CLIMATE') {
            $highlightClass = 'climate-flag'; // Add CSS class for light blue background
        }

        echo "<td class='$highlightClass'>$cellValue</td>";

        // Collect data for summary calculations
        if ($colName != 'Date') {
            $metaInfo = getMetadataByDisplayName($colName, $metadata);
            if ($metaInfo) {
                $viewType = $metaInfo['view_type'];
                if ($viewType === 'numeric') {
                    if (is_numeric($cellValue)) {
                        $columnData[$colName][] = $cellValue;
                    } else {
                        $columnData[$colName][] = 'N/A';
                    }
                } elseif ($viewType === 'text') {
                    if ($cellValue !== 'N/A') {
                        // For wind direction, store the degree values
                        $originalDegrees = isset($row[$colName . '_degrees']) ? $row[$colName . '_degrees'] : null;
                        if ($originalDegrees !== null) {
                            $columnData[$colName][] = $originalDegrees;
                        } else {
                            $columnData[$colName][] = 'N/A';
                        }
                    } else {
                        $columnData[$colName][] = 'N/A';
                    }
                }
            } else {
                // No metadata, set to 'N/A'
                $columnData[$colName][] = 'N/A';
            }
        }
    }
    echo "</tr>";
}


// Get the total number of rows in the data
$totalRows = count($tableData);

$summarySumRow = [];
$summaryMeanRow = [];

foreach ($columns as $colName) {
    if ($colName == 'Date') {
        $summarySumRow[$colName] = '<b>Summary Sum</b>';
        $summaryMeanRow[$colName] = '<b>Summary Mean</b>';
    } else {
        $metaInfo = getMetadataByDisplayName($colName, $metadata);
        if ($metaInfo) {
            $precision = getPrecision($metaInfo['precision_type']);
            $viewType = $metaInfo['view_type'];

            $values = $columnData[$colName];

            // Exclude Max HI and Min WC from summary calculations
            if (in_array($colName, ['Max HI', 'Min WC'])) {
                $summarySumRow[$colName] = '<b>N/A</b>';
                $summaryMeanRow[$colName] = '<b>N/A</b>';
                continue;
            }

            if ($viewType === 'numeric') {
                $hasNAValues = array_reduce($values, function ($carry, $value) {
                    return $carry || $value === 'N/A';
                }, false);

                if ($hasNAValues) {
                    $summarySumRow[$colName] = '<b>N/A</b>';
                    $summaryMeanRow[$colName] = '<b>N/A</b>';
                } else {
                    $validValues = array_filter($values, function ($value) {
                        return is_numeric($value);
                    });
                    $validCount = count($validValues);

                    if ($validCount === 0) {
                        $summarySumRow[$colName] = '<b>N/A</b>';
                        $summaryMeanRow[$colName] = '<b>N/A</b>';
                    } else {
                        if (strpos($colName, 'Precip') !== false) {
                            $summarySumRow[$colName] = '<b>' . round(array_sum($validValues), $precision) . '</b>';
                            $summaryMeanRow[$colName] = '<b>N/A</b>';
                        } else {
                            $sum = array_sum($validValues);
                            $mean = $sum / $validCount;
                            $summarySumRow[$colName] = '<b>' . round($sum, $precision) . '</b>';
                            $summaryMeanRow[$colName] = '<b>' . round($mean, $precision) . '</b>';
                        }
                    }
                }
            } elseif ($viewType === 'text' && $colName === 'Wind Dir.') {
                // Handle wind direction averaging
                $validValues = array_filter($values, function ($value) {
                    return is_numeric($value);
                });
                if (count($validValues) === 0) {
                    $summarySumRow[$colName] = '<b>N/A</b>';
                    $summaryMeanRow[$colName] = '<b>N/A</b>';
                } else {
                    $sumSin = 0;
                    $sumCos = 0;
                    foreach ($validValues as $deg) {
                        $rad = deg2rad($deg);
                        $sumSin += sin($rad);
                        $sumCos += cos($rad);
                    }
                    $avgRad = atan2($sumSin, $sumCos);
                    $avgDeg = rad2deg($avgRad);
                    if ($avgDeg < 0) {
                        $avgDeg += 360;
                    }
                    $avgDir = degreesToWindDirection($avgDeg);
                    $summarySumRow[$colName] = '<b>N/A</b>';
                    $summaryMeanRow[$colName] = '<b>' . $avgDir . '</b>';
                }
            } else {
                $summarySumRow[$colName] = '<b>N/A</b>';
                $summaryMeanRow[$colName] = '<b>N/A</b>';
            }
        } else {
            // No metadata, set to 'N/A'
            $summarySumRow[$colName] = '<b>N/A</b>';
            $summaryMeanRow[$colName] = '<b>N/A</b>';
        }
    }
}

// Display the results
echo "<tr>";
foreach ($columns as $colName) {
    echo "<td>" . $summarySumRow[$colName] . "</td>";
}
echo "</tr>";

echo "<tr>";
foreach ($columns as $colName) {
    echo "<td>" . $summaryMeanRow[$colName] . "</td>";
}
echo "</tr>";


echo "</tbody></table>";


?>











<!-- Save to CSV Button -->
<a id="saveCsvButton" href="#">Save to CSV</a>

<script>
const DATA_TABLE_CONFIG = {
    scrollY: "500px",
    scrollX: true,
    paging: false,
    searching: false,
    sorting: false,
    info: false,
    autoWidth: true
};

const COLUMN_WIDTHS = {
    date: "150px", // Explicit width for the Date column
    default: "100px"
};

const columnTypeMapping = {
    temp: "temperature",
    "dew point": "dew_point",
    gust: "wind_gust",
    wind: "wind",
    precip: "precip",
    hi: "heat_index",
    wc: "wind_chill"
};

function setDropdownValue(dropdownId, value) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        const option = Array.from(dropdown.options).find(option => option.value.toUpperCase() === value.toUpperCase());
        if (option) option.selected = true;
    }
}

function getColumnType(colIndex, table) {
    const header = table.querySelector(`thead tr th:nth-child(${colIndex + 1})`);
    if (header) {
        const headerText = header.textContent.toLowerCase();
        for (const [key, type] of Object.entries(columnTypeMapping)) {
            if (headerText.includes(key)) return type;
        }
    }
    return null;
}

document.addEventListener('DOMContentLoaded', () => {
    const { station, date } = getQueryParams();

    // Pre-fill the station input
    if (station) {
        document.getElementById('station').value = station;
    }

    // Pre-fill the month and year dropdowns
    if (date && date.includes('_')) {
        const [month, year] = date.split('_');
        setDropdownValue('month', month);
        setDropdownValue('year', year);
    }

    const table = $('#dataTable');

    if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy(); // Destroy existing instance if present
    }

    const basicColumns = [true].concat(<?php echo json_encode(array_map(function($meta) {
        return $meta['display_type'] === 'basic';
    }, $metadata)); ?>);


    const dataTable = table.DataTable({
        ...DATA_TABLE_CONFIG,
        columnDefs: [
            { targets: 0, width: COLUMN_WIDTHS.date }, // Set explicit width for Date column
            ...basicColumns.map((isBasic, index) => ({
                targets: index,
                visible: isBasic,
                width: COLUMN_WIDTHS.default
            }))
        ]
    });

    const precipColumnIndex = table.find('thead tr:first-child th').toArray().findIndex(th =>
        $(th).text().toLowerCase().includes('precip')
    );

    if (precipColumnIndex !== -1) {
        const rows = table.find('tbody tr');
        let consecutiveSum = 0;
        let consecutiveStartRow = null;

        rows.each(function (rowIndex) {
            const cell = $(this).find('td').eq(precipColumnIndex);
            const cellValue = parseFloat(cell.text());
            const isClimateFlagged = cell.hasClass('climate-flag');

            if (isClimateFlagged) {
                if (consecutiveStartRow === null) {
                    consecutiveStartRow = $(this);
                }
                if (!isNaN(cellValue)) {
                    consecutiveSum += cellValue;
                }
                cell.text('');
            } else if (consecutiveStartRow !== null) {
                consecutiveStartRow
                    .find('td')
                    .eq(precipColumnIndex)
                    .text(`${consecutiveSum.toFixed(2)}*`);
                consecutiveStartRow = null;
                consecutiveSum = 0;
            }
        });
    }

    $('.toggle-display').on('click', function () {
        const displayType = $(this).data('display');
        dataTable.columns().every(function (index) {
            const meta = <?php echo json_encode($metadata); ?>[index - 1];
            if (meta) {
                this.visible(meta.display_type === displayType);
            }
        });

        // Adjust column widths after toggling the display
      dataTable.columns.adjust().draw();
    });
});

function getQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        station: params.get('station'),
        date: params.get('date')
    };
}

function refreshWithNewParams() {
    // Get the input values for station and date
    const station = document.getElementById("station").value;
    const month = document.getElementById("month").value;
    const year = document.getElementById("year").value;
    
    const date = document.getElementById("month").value + '_' + document.getElementById("year").value;



    // Validate inputs (optional)
    if (!station || !date) {
        alert("Please provide both station and date values.");
        return;
    }

    // Construct the new URL with query parameters
    const newUrl = `${window.location.origin}${window.location.pathname}?station=${station}&date=${date}`;

    // Reload the page with the new URL
    window.location.href = newUrl;
}
document.addEventListener("DOMContentLoaded", function () {
    const table = document.querySelector("#dataTable"); // Select table by ID
    if (!table) {
        console.error("Table not found");
        return;
    }

    const rows = Array.from(table.querySelectorAll("tbody tr")); // Get all rows in the table body
    if (rows.length < 3) {
        console.error("Not enough rows in the table for processing");
        return;
    }

    // Exclude the last two rows (summary rows) from processing
    const dataRows = rows.slice(0, -2);
    if (dataRows.length === 0) {
        console.error("No data rows found");
        return;
    }

    const columns = Array.from(dataRows[0].children).map((_, index) => index); // Get column indices

    // Helper function to check the type of column based on its header
    const getColumnType = (colIndex) => {
        const header = table.querySelector(`thead tr th:nth-child(${colIndex + 1})`);
        if (header) {
            const headerText = header.textContent.toLowerCase();
            if (headerText.includes("temp")) return "temperature";
            if (headerText.includes("dew point")) return "dew_point";
            if (headerText.includes("gust")) return "wind_gust";
            if (headerText.includes("wind")) return "wind";
            if (headerText.includes("precip")) return "precip";
            if (headerText.includes("hi")) return "heat_index";
            if (headerText.includes("wc")) return "wind_chill";
        }
        return null;
    };

    // Iterate through each column
    columns.forEach((colIndex) => {
        const columnType = getColumnType(colIndex);

        if (columnType) {
            const values = [];
            dataRows.forEach((row) => {
                const cell = row.children[colIndex];
                if (cell) {
                    const value = parseFloat(cell.textContent.trim());
                    if (!isNaN(value)) {
                        values.push(value);
                    }
                }
            });

            if (values.length > 0) {
                const maxValue = Math.max(...values);
                const minValue = Math.min(...values);

                dataRows.forEach((row) => {
                    const cell = row.children[colIndex];
                    if (cell) {
                        const value = parseFloat(cell.textContent.trim());
                        if (!isNaN(value)) {
                            // Apply specific styling based on column type
                            if (["temperature", "dew_point"].includes(columnType)) {
                                if (value === maxValue) {
                                    cell.style.color = "red";
                                    cell.style.fontWeight = "bold";
                                } else if (value === minValue) {
                                    cell.style.color = "blue";
                                    cell.style.fontWeight = "bold";
                                }
                            } else if (["wind_gust", "wind"].includes(columnType)) {
                                if (value === maxValue) {
                                    cell.style.color = "green";
                                    cell.style.fontWeight = "bold";
                                }
                            } else if (columnType === "precip") {
                                if (value === maxValue) {
                                    cell.style.color = "green";
                                    cell.style.fontWeight = "bold";
                                }
                            } else if (columnType === "heat_index") {
                                if (value === maxValue) {
                                    cell.style.color = "red";
                                    cell.style.fontWeight = "bold";
                                }
                            } else if (columnType === "wind_chill") {
                                if (value === minValue) {
                                    cell.style.color = "blue";
                                    cell.style.fontWeight = "bold";
                                }
                            }
                        }
                    }
                });
            }
        }
    });
});



</script>


</body>
</html>
