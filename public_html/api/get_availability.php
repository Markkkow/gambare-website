<?php
require_once __DIR__ . '/_helpers.php';

checkApiKey();

$date = isset($_GET['date']) ? $_GET['date'] : null;
if (!$date || !isValidDate($date)) {
    jsonError('Parameter tanggal tidak valid.', 400);
}

$bookings = readBookings();
$booked = 0;

foreach ($bookings as $b) {
    if (
        isset($b['date'], $b['status'], $b['tables']) &&
        $b['date'] === $date &&
        $b['status'] === 'pending'
    ) {
        $booked += (int) $b['tables'];
    }
}

echo json_encode(array(
    'success' => true,
    'date' => $date,
    'total' => TOTAL_TABLES,
    'booked' => $booked,
    'available' => max(0, TOTAL_TABLES - $booked)
));
