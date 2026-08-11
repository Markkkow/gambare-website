<?php
require_once __DIR__ . '/_helpers.php';

checkApiKey();

$date = isset($_GET['date']) ? $_GET['date'] : null;
if (!$date || !isValidDate($date)) {
    jsonError('Parameter tanggal tidak valid.', 400);
}

$bookings = readBookings();

$filtered = array_values(array_filter($bookings, function ($b) use ($date) {
    return isset($b['date']) && $b['date'] === $date;
}));

usort($filtered, function ($a, $b) {
    $timeA = isset($a['time']) ? $a['time'] : '';
    $timeB = isset($b['time']) ? $b['time'] : '';
    return strcmp($timeA, $timeB);
});

echo json_encode(array(
    'success' => true,
    'date' => $date,
    'bookings' => $filtered
));
