<?php
// process.php
header('Content-Type: application/json');

/**
 * Fetch JSON data for a given month and station.
 */
function fetchMonthData($year, $month, $station = "DIRL") {
    // Calculate the start and end dates of the month.
    $start_date = date("Y-m-01", strtotime("$year-$month-01"));
    $end_date   = date("Y-m-t", strtotime("$year-$month-01"));
    
    // Build the URL with query parameters.
    $url = "http://150.136.239.199/almanac/retrieve.old.php?start={$start_date}&end={$end_date}&station_name={$station}";
    
    // Fetch the JSON data (make sure allow_url_fopen is enabled)
    $json_data = file_get_contents($url);
    if ($json_data === false) {
        return false;
    }
    return json_decode($json_data, true);
}

/**
 * Compute the average for the specified data_type_id.
 */
function computeAverage($data, $station = "DIRL", $data_type_id = "44") {
    $total = 0.0;
    $count = 0;
    
    if (isset($data['data'][$station]) && is_array($data['data'][$station])) {
        foreach ($data['data'][$station] as $datetime => $records) {
            if (isset($records[$data_type_id])) {
                $value = floatval($records[$data_type_id]['VALUE']);
                $total += $value;
                $count++;
            }
        }
    }
    
    if ($count === 0) {
        return null;
    }
    return $total / $count;
}

$results = [];

// Loop through years 2020 to 2024.
for ($year = 2020; $year <= 2024; $year++) {
    for ($month = 1; $month <= 12; $month++) {
        $data = fetchMonthData($year, $month, "DIRL");
        $month_label = date("M Y", strtotime("$year-$month-01"));
        
        if ($data === false) {
            $results[] = ['month' => $month_label, 'average' => "Error fetching data"];
            continue;
        }
        
        $avg = computeAverage($data, "DIRL", "44");
        if ($avg === null) {
            $results[] = ['month' => $month_label, 'average' => "No data"];
        } else {
            // Convert from Kelvin to Fahrenheit.
            $avgF = ($avg - 273.15) * 9/5 + 32;
            $results[] = ['month' => $month_label, 'average' => round($avgF, 1) . "°F"];
        }
    }
}

echo json_encode($results);
