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

        /* Sticky first column */
        table.dataTable tbody tr td:first-child,
        table.dataTable thead tr th:first-child {
            position: sticky;
            left: 0;
            z-index: 2;
            background-color: #ffffff;
            box-shadow: 1px 0 2px rgba(0, 0, 0, 0.1);
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
            cursor: default;
        }

        /* Styling for the last row in the table */
        #dataTable tbody tr:last-child {
            background-color: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
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
            for (let y = currentYear; y >= startYear; y--) {
                const option = document.createElement('option');
                option.value = y;
                option.textContent = y;
                yearDropdown.appendChild(option);
            }
        </script>
    </select>

    <button id="submitButton" onclick="refreshWithNewParams()">Submit</button>
</div>

<?php
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
        $start_date = "$year-$monthNumber-01";
        $end_date = date("Y-m-t", strtotime($start_date));

        // JSON data URL
        $jsonFile = "http://150.136.239.199/almanac/retrieve.old.php?start=$start_date&end=$end_date&station_name=$station_name";
    } else {
        die("Invalid month or year format provided.<br>");
    }
} else {
    die("Date must be in the format 'MMM_YYYY', e.g., 'JUN_2023'.<br>");
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);
if ($data === null) {
    echo "<p>Error decoding JSON data. Please check the data format.</p>";
    exit;
}

// Metadata array
$metadata = [
    ["data_name_full" => "Mean Daily Temp.",           "data_name_display" => "Mean Temp.",       "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Max Daily Temp.",            "data_name_display" => "Max Temp.",        "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Min Daily Temp.",            "data_name_display" => "Min Temp.",        "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Mean Wind Speed",            "data_name_display" => "Wind Speed",       "conversion_type" => "ms_to_mph",            "precision_type" => 0, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Mean Wind Direction",        "data_name_display" => "Wind Dir.",        "conversion_type" => "rad_to_degrees",       "precision_type" => 0, "view_type" => "text",    "display_type" => "basic"],
    ["data_name_full" => "Peak Wind Gust Speed (Daily)","data_name_display" => "Max Gust",         "conversion_type" => "ms_to_mph",            "precision_type" => 0, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Gage Precipitation (Daily)", "data_name_display" => "Precip",           "conversion_type" => "mm_to_inches",         "precision_type" => 2, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Mean Daily Dew Point",       "data_name_display" => "Dew Point",        "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Daily Min WC",               "data_name_display" => "Min WC",           "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],
    ["data_name_full" => "Daily Max HI",               "data_name_display" => "Max HI",           "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "basic"],

    ["data_name_full" => "Mean Water Temp",            "data_name_display" => "Water Temp.",      "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "water"],
    ["data_name_full" => "Max Daily Water Temp",       "data_name_display" => "Max Water Temp.",  "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "water"],
    ["data_name_full" => "Min Daily Water Temp",       "data_name_display" => "Min Water Temp.",  "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "water"],
    ["data_name_full" => "Mean Daily Well Level",      "data_name_display" => "Well Level",       "conversion_type" => "none",                "precision_type" => 2, "view_type" => "numeric", "display_type" => "water"],

    ["data_name_full" => "Daily Max RH",               "data_name_display" => "Max RH",           "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Daily Min RH",               "data_name_display" => "Min RH",           "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Daily Max ST",               "data_name_display" => "Max ST",           "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Daily Min ST",               "data_name_display" => "Min ST",           "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Heating Degree Days",        "data_name_display" => "HDD",              "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],
    ["data_name_full" => "Cooling Degree Days",        "data_name_display" => "CDD",              "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "other"],

    ["data_name_full" => "Mean Daily RH",              "data_name_display" => "Mean RH",          "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "Daily Avg ST",               "data_name_display" => "Avg ST",           "conversion_type" => "kelvin_to_fahrenheit", "precision_type" => 1, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "Daily Solar",                "data_name_display" => "Solar",            "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "Reference Evapotrans.",      "data_name_display" => "Ref. ET",          "conversion_type" => "mm_to_inches",         "precision_type" => 2, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "GDD0C",                      "data_name_display" => "GDD (0°C)",        "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"],
    ["data_name_full" => "GDD32F",                     "data_name_display" => "GDD (32°F)",       "conversion_type" => "none",                "precision_type" => 0, "view_type" => "numeric", "display_type" => "ag"]
];

// Conversion function
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

// Precision
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
    $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE',
                   'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];

    return $directions[$index];
}

// Get unit label
function getUnitLabel($conversionType, $units) {
    switch ($conversionType) {
        case 'kelvin_to_fahrenheit':
            return "°F";
        case 'ms_to_mph':
            return "mph";
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
$tableData = [];
$columnData = [];
$columnPrecision = [];
$flaggedData = [];

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
                        $conversionType = $metaInfo['conversion_type'];
                        $precisionType = $metaInfo['precision_type'];
                        $viewType = $metaInfo['view_type'];
                        $units = $info['UNITS'];
                        $unitLabel = getUnitLabel($conversionType, $units);
                        $uniqueDataTypes[$dataTypeDisplay] = $unitLabel;

                        $value = $info['VALUE'];
                        $precision = getPrecision($precisionType);
                        $columnPrecision[$dataTypeDisplay] = $precision;

                        // Flags
                        if ($info['FLAG'] == '1' || $info['FLAG'] == '7') {
                            $convertedValue = convertToEnglishUnits($value, $conversionType);

                            if ($viewType === 'text') {
                                // Wind direction
                                $row[$dataTypeDisplay] = degreesToWindDirection($convertedValue);
                                $row[$dataTypeDisplay . '_degrees'] = $convertedValue;
                            } else {
                                $row[$dataTypeDisplay] = round($convertedValue, $precision);
                            }

                            // Climate flag
                            if ($info['FLAG'] == '7') {
                                $flaggedData[$formattedDate][$dataTypeDisplay] = 'CLIMATE';
                            }
                        } else {
                            // Non-1/7 flags
                            $row[$dataTypeDisplay] = 'N/A';
                        }
                    } else {
                        // No metadata
                        $row[$info['DATA_TYPE']] = 'N/A';
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
        // Highlight flagged data
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
                    $columnData[$colName][] = is_numeric($cellValue) ? $cellValue : 'N/A';
                } elseif ($viewType === 'text') {
                    // For wind direction, we stored degrees in _degrees
                    $originalDegrees = $row[$colName . '_degrees'] ?? 'N/A';
                    $columnData[$colName][] = is_numeric($originalDegrees) ? $originalDegrees : 'N/A';
                }
            } else {
                $columnData[$colName][] = 'N/A';
            }
        }
    }
    echo "</tr>";
}

// Calculate summary rows
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

            // Exclude these from summary calculations
            if (in_array($colName, ['Max HI', 'Min WC'])) {
                $summarySumRow[$colName] = '<b>N/A</b>';
                $summaryMeanRow[$colName] = '<b>N/A</b>';
                continue;
            }

            if ($viewType === 'numeric') {
                // Check if all values are numeric
                $validValues = array_filter($values, 'is_numeric');

                if (empty($validValues)) {
                    // No valid numeric values
                    $summarySumRow[$colName] = '<b>N/A</b>';
                    $summaryMeanRow[$colName] = '<b>N/A</b>';
                } else {
                    // Special handling for precip (Sum only)
                    if (stripos($colName, 'precip') !== false) {
                        $summarySumRow[$colName] = '<b>' . round(array_sum($validValues), $precision) . '</b>';
                        $summaryMeanRow[$colName] = '<b>N/A</b>';
                    } else {
                        $sum = array_sum($validValues);
                        $mean = $sum / count($validValues);
                        $summarySumRow[$colName] = '<b>' . round($sum, $precision) . '</b>';
                        $summaryMeanRow[$colName] = '<b>' . round($mean, $precision) . '</b>';
                    }
                }
            } elseif ($viewType === 'text' && $colName === 'Wind Dir.') {
                // Averaging wind direction
                $validValues = array_filter($values, 'is_numeric');
                if (empty($validValues)) {
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
            // No metadata
            $summarySumRow[$colName] = '<b>N/A</b>';
            $summaryMeanRow[$colName] = '<b>N/A</b>';
        }
    }
}

// Add summary rows
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
// DataTable configuration
const DATA_TABLE_CONFIG = {
    scrollY: "500px",
    scrollX: true,
    paging: false,
    searching: false,
    sorting: false,
    info: false,
    autoWidth: true
};

// Helper to set dropdown value
function setDropdownValue(dropdownId, value) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        const option = Array.from(dropdown.options).find(opt => opt.value.toUpperCase() === value.toUpperCase());
        if (option) option.selected = true;
    }
}

// Get query params
function getQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        station: params.get('station'),
        date: params.get('date')
    };
}

// Refresh page with new parameters
function refreshWithNewParams() {
    const station = document.getElementById("station").value;
    const month = document.getElementById("month").value;
    const year = document.getElementById("year").value;
    const date = month + '_' + year;

    if (!station || !date) {
        alert("Please provide both station and date values.");
        return;
    }

    const newUrl = `${window.location.origin}${window.location.pathname}?station=${station}&date=${date}`;
    window.location.href = newUrl;
}

document.addEventListener('DOMContentLoaded', () => {
    const { station, date } = getQueryParams();

    // Pre-fill station input
    if (station) {
        document.getElementById('station').value = station;
    }

    // Pre-fill month/year dropdowns
    if (date && date.includes('_')) {
        const [m, y] = date.split('_');
        setDropdownValue('month', m);
        setDropdownValue('year', y);
    }

    const table = $('#dataTable');

    // Initialize DataTable
    if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy();
    }

    const metadata = <?php echo json_encode($metadata); ?>;
    const basicColumns = [true].concat(metadata.map(m => m.display_type === 'basic'));

    const dataTable = table.DataTable({
        ...DATA_TABLE_CONFIG,
        columnDefs: [
            { targets: 0, width: "150px" }, // Date column
            ...basicColumns.map((isBasic, index) => ({
                targets: index,
                visible: isBasic,
                width: "100px"
            }))
        ]
    });

    // Handle consecutive climate-flagged precip
    const precipColumnIndex = $('#dataTable thead tr:first-child th').toArray().findIndex(th =>
        $(th).text().toLowerCase().includes('precip')
    );

    if (precipColumnIndex !== -1) {
        const rows = $('#dataTable tbody tr');
        let consecutiveSum = 0;
        let consecutiveStartRow = null;

        rows.each(function () {
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
                // Place the total in the start row
                consecutiveStartRow
                    .find('td')
                    .eq(precipColumnIndex)
                    .text(`${consecutiveSum.toFixed(2)}*`);
                consecutiveStartRow = null;
                consecutiveSum = 0;
            }
        });
    }

    // Column toggle buttons
    $('.toggle-display').on('click', function () {
        const displayType = $(this).data('display');
        dataTable.columns().every(function (index) {
            if (index === 0) return; // Always show Date column
            const meta = metadata[index - 1];
            if (meta) {
                this.visible(meta.display_type === displayType);
            }
        });
        dataTable.columns.adjust().draw();
    });

    // Highlight min/max values in certain columns
    highlightExtremes();
});

// Highlight extremes based on column type
function highlightExtremes() {
    const table = document.querySelector("#dataTable");
    if (!table) return;

    const rows = Array.from(table.querySelectorAll("tbody tr"));
    if (rows.length < 3) return;

    // Exclude summary rows (last two)
    const dataRows = rows.slice(0, -2);
    if (dataRows.length === 0) return;

    const columnsCount = dataRows[0].children.length;

    for (let colIndex = 0; colIndex < columnsCount; colIndex++) {
        const columnType = detectColumnType(colIndex, table);
        if (!columnType) continue;

        const values = [];
        dataRows.forEach((row) => {
            const cell = row.children[colIndex];
            const val = parseFloat(cell.textContent.trim());
            if (!isNaN(val)) values.push(val);
        });

        if (values.length === 0) continue;

        const maxValue = Math.max(...values);
        const minValue = Math.min(...values);

        dataRows.forEach((row) => {
            const cell = row.children[colIndex];
            const val = parseFloat(cell.textContent.trim());
            if (isNaN(val)) return;

            // Apply coloring rules
            if (["temperature", "dew_point"].includes(columnType)) {
                if (val === maxValue) {
                    cell.style.color = "red";
                    cell.style.fontWeight = "bold";
                } else if (val === minValue) {
                    cell.style.color = "blue";
                    cell.style.fontWeight = "bold";
                }
            } else if (["wind_gust", "wind"].includes(columnType) || columnType === "precip") {
                if (val === maxValue) {
                    cell.style.color = "green";
                    cell.style.fontWeight = "bold";
                }
            } else if (columnType === "heat_index") {
                if (val === maxValue) {
                    cell.style.color = "red";
                    cell.style.fontWeight = "bold";
                }
            } else if (columnType === "wind_chill") {
                if (val === minValue) {
                    cell.style.color = "blue";
                    cell.style.fontWeight = "bold";
                }
            }
        });
    }
}

// Detect column type by header
function detectColumnType(colIndex, table) {
    const header = table.querySelector(`thead tr th:nth-child(${colIndex + 1})`);
    if (!header) return null;

    const headerText = header.textContent.toLowerCase();

    if (headerText.includes("temp")) return "temperature";
    if (headerText.includes("dew point")) return "dew_point";
    if (headerText.includes("gust")) return "wind_gust";
    if (headerText.includes("wind") && !headerText.includes("gust")) return "wind";
    if (headerText.includes("precip")) return "precip";
    if (headerText.includes("hi")) return "heat_index";
    if (headerText.includes("wc")) return "wind_chill";

    return null;
    //test github
}
</script>
</body>
</html>
