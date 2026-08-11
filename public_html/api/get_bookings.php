<?php
/* ════════════════════════════════════════════════
   get_bookings.php — daftar reservasi lengkap untuk kasir
   Contoh: GET /api/get_bookings.php?date=2026-07-09&key=XXX
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
$filtered = array_values(array_filter($bookings, fn($b) => $b['date'] === $date));

// Urutkan berdasarkan jam kedatangan
usort($filtered, fn($a, $b) => strcmp($a['time'], $b['time']));

echo json_encode(['success' => true, 'date' => $date, 'bookings' => $filtered]);
