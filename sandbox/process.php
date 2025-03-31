<?php
// process.php
header('Content-Type: application/json');

/**
 * Fetch JSON data for a given month and station.
 */
function fetchMonthData($year, $month, $station = "DGES") {
    $month_str = sprintf("%02d", $month);
    $start_date = date("Y-m-01", strtotime("$year-$month_str-01"));
    $end_date   = date("Y-m-t", strtotime("$year-$month_str-01"));
    $url = "http://150.136.239.199/almanac/retrieve.old.php?raw_values=y&start={$start_date}&end={$end_date}&station_name={$station}";
    $json_data = file_get_contents($url);
    if ($json_data === false) {
        return false;
    }
    return ['url' => $url, 'decoded' => json_decode($json_data, true), 'raw' => $json_data];
}

$years = [2020,2021,2022,2023,2024,2025]; // Process both 2023 and 2024
$results = [];

foreach ($years as $year) {
    $endMonth = ($year == 2025) ? 3 : 12;
    foreach (range(1, $endMonth) as $month) {
        $result = fetchMonthData($year, $month, "DGES");
        if ($result === false) {
            continue;
        }
        $data = $result['decoded'];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $stationId => $stationData) {
                if (is_array($stationData)) {
                    foreach ($stationData as $datetime => $records) {
                        $date = substr($datetime, 0, 11);
                        if (isset($records["51"])) {
                            $value51 = ($records["51"]['FLAG'] === "1")
                                ? round(floatval($records["51"]['VALUE']) / 25.4, 2)
                                : "--";
                        } else {
                            $value51 = "--";
                        }
                        $results[] = ['date' => $date, 'value51' => $value51];
                    }
                }
            }
        }
    }
}

echo json_encode(['results' => $results]);
