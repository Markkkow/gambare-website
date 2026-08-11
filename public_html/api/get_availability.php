<?php
/* ════════════════════════════════════════════════
   get_availability.php — cek sisa meja untuk tanggal tertentu
   Dipanggil dari index.html (customer-facing)
   Contoh: GET /api/get_availability.php?date=2026-07-09&key=XXX
═══════════════════════════════════════════════════ */
require_once __DIR__ . '/_helpers.php';

checkApiKey();

$date = $_GET['date'] ?? null;
if (!$date || !isValidDate($date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter tanggal tidak valid.']);
    exit;
}

$bookings = readBookings();
$booked = 0;
foreach ($bookings as $b) {
    if ($b['date'] === $date && $b['status'] === 'pending') {
        $booked += (int) $b['tables'];
    }
}

echo json_encode([
    'success'   => true,
    'date'      => $date,
    'total'     => TOTAL_TABLES,
    'booked'    => $booked,
    'available' => max(0, TOTAL_TABLES - $booked),
]);
