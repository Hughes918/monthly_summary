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
$years     = range($startYear, $endYear);

// Conversion lookup: conversion functions for each data type.
$conversions = [
    '51' => function($v) { return round($v / 25.4, 2); },              // mm to in
    '43'  => function($v) { return round((($v - 273.15) * 9/5) + 32, 2); }, // Kelvin to Fahrenheit
    '70' => function($v) { return round($v * 3.28084, 2); }              // m to ft
];

/**
 * Fetch JSON data for a given month and station.
 */
function fetchMonthData($year, $month, $station) {
    $month_str = sprintf("%02d", $month);
    $start_date = date("Y-m-01", strtotime("$year-$month_str-01"));
    $end_date   = date("Y-m-t", strtotime("$year-$month_str-01"));
    $url = "http://150.136.239.199/almanac/retrieve.old.php?raw_values=y&start={$start_date}&end={$end_date}&station_name={$station}";
    $json_data = file_get_contents($url);
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

foreach ($years as $year) {
    $endMonth = ($year == 2025) ? 3 : 12;
    foreach (range(1, $endMonth) as $month) {
        $result = fetchMonthData($year, $month, $station);
        if ($result === false) {
            continue;
        }
        $data = $result['decoded'];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $stationId => $stationData) {
                if (is_array($stationData)) {
                    foreach ($stationData as $datetime => $records) {
                        $date = substr($datetime, 0, 11);
                        if (isset($records[$GLOBALS['dataType']])) {
                            if ($records[$GLOBALS['dataType']]['FLAG'] === "1") {
                                $rawValue = floatval($records[$GLOBALS['dataType']]['VALUE']);
                                $value = isset($GLOBALS['conversions'][$GLOBALS['dataType']])
                                    ? $GLOBALS['conversions'][$GLOBALS['dataType']]($rawValue)
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

// Clear any unexpected output, flush and exit.
ob_clean();
echo json_encode(['results' => $results]);
flush();
exit;
