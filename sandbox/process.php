<?php
// process.php
header('Content-Type: application/json');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// Retrieve parameters from GET (definitions provided in index.html)
$station   = isset($_GET['station']) ? $_GET['station'] : '';
$dataType  = isset($_GET['dataType']) ? $_GET['dataType'] : '';
$startYear = isset($_GET['startYear']) ? intval($_GET['startYear']) : 0;
$endYear   = isset($_GET['endYear']) ? intval($_GET['endYear']) : 0;
$month     = isset($_GET['month']) ? intval($_GET['month']) : 0;

// Conversion lookup: conversion functions for each data type.
$conversions = [
    '51' => function($v) { return round($v / 25.4, 2); },              // mm to in
    '43'  => function($v) { return round((($v - 273.15) * 9/5) + 32, 2); }, // Kelvin to Fahrenheit
    '44'  => function($v) { return round((($v - 273.15) * 9/5) + 32, 2); }, // Kelvin to Fahrenheit
    '45'  => function($v) { return round((($v - 273.15) * 9/5) + 32, 2); }, // Kelvin to Fahrenheit
    '70' => function($v) { return round($v * 3.28084, 2); },              // m to ft
    // New conversion: m/s to mph (1 m/s ≈ 2.23694 mph)
    '50' => function($v) { return round($v * 2.23694, 1); },
     '184'  => function($v) { return round((($v - 273.15) * 9/5) + 32, 2); }, // Kelvin to Fahrenheit
];

/**
 * Fetch JSON data for a given month and station.
 */
function fetchMonthData($year, $month, $station) {
    $month_str = sprintf("%02d", $month);
    $start_date = date("Y-m-01", strtotime("$year-$month_str-01"));
    $end_date   = date("Y-m-t", strtotime("$year-$month_str-01"));
    $url = "http://150.136.239.199/almanac/retrieve.old.php?start={$start_date}&end={$end_date}&station_name={$station}&raw_values=Y";
    $json_data = @file_get_contents($url);
    if ($json_data === false) {
        return false;
    }
    $decoded = json_decode($json_data, true);
    // If JSON is invalid (or lacks "data"), skip this month
    if (!$decoded || !is_array($decoded) || empty($decoded['data'])) {
        return false;
    }
    return ['url' => $url, 'decoded' => $decoded, 'raw' => $json_data];
}

$results = [];

if ($month) {
    // Single month
    $result = fetchMonthData($startYear, $month, $station);
    if ($result !== false) {
        $data = $result['decoded'];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $stationId => $stationData) {
                if (is_array($stationData)) {
                    foreach ($stationData as $datetime => $records) {
                        $date = substr($datetime, 0, 11);
                        if (isset($records[$dataType])) {
                            if ($records[$dataType]['FLAG'] === "1") {
                                $rawValue = floatval($records[$dataType]['VALUE']);
                                $value = isset($conversions[$dataType])
                                    ? $conversions[$dataType]($rawValue)
                                    : $rawValue;
                            } else {
                                $value = "--";
                            }
                        } else {
                            $value = "--";
                        }
                        $results[] = ['date' => $date, 'value' => $value];
                    }
                }
            }
        }
    }
} else {
    // All months in the year range
    for ($year = $startYear; $year <= $endYear; $year++) {
        for ($m = 1; $m <= 12; $m++) {
            $result = fetchMonthData($year, $m, $station);
            if ($result !== false) {
                $data = $result['decoded'];
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $stationId => $stationData) {
                        if (is_array($stationData)) {
                            foreach ($stationData as $datetime => $records) {
                                $date = substr($datetime, 0, 11);
                                if (isset($records[$dataType])) {
                                    if ($records[$dataType]['FLAG'] === "1") {
                                        $rawValue = floatval($records[$dataType]['VALUE']);
                                        $value = isset($conversions[$dataType])
                                            ? $conversions[$dataType]($rawValue)
                                            : $rawValue;
                                    } else {
                                        $value = "--";
                                    }
                                } else {
                                    $value = "--";
                                }
                                $results[] = ['date' => $date, 'value' => $value];
                            }
                        }
                    }
                }
            }
        }
    }
}

// Clear any unexpected output, flush and exit.
ob_clean();
echo json_encode(['results' => $results]);
flush();
exit;
