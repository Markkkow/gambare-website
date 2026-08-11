<?php
require_once __DIR__ . '/_helpers.php';

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

checkApiKey();
$input = getJsonBody();

if (!$input) {
    jsonError('Data tidak valid.', 400);
}

$required = array('name', 'phone', 'date', 'time', 'floor', 'tables', 'guests');
foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        jsonError("Field '$field' wajib diisi.", 400);
    }
}

if (!isValidDate($input['date'])) {
    jsonError('Format tanggal tidak valid.', 400);
}

$tables = (int) $input['tables'];
if ($tables < 1 || $tables > TOTAL_TABLES) {
    jsonError('Jumlah meja tidak valid.', 400);
}

$bookings = readBookings();
$bookedToday = 0;

foreach ($bookings as $b) {
    if (
        isset($b['date'], $b['status'], $b['tables']) &&
        $b['date'] === $input['date'] &&
        $b['status'] === 'pending'
    ) {
        $bookedToday += (int) $b['tables'];
    }
}

if ($bookedToday + $tables > TOTAL_TABLES) {
    jsonError('Meja tidak cukup. Sisa: ' . (TOTAL_TABLES - $bookedToday), 409);
}

$newBooking = array(
    'id' => uniqid('bk_', true),
    'name' => strip_tags(trim($input['name'])),
    'phone' => preg_replace('/[^0-9+]/', '', $input['phone']),
    'date' => $input['date'],
    'time' => $input['time'],
    'floor' => strip_tags(trim($input['floor'])),
    'tables' => $tables,
    'guests' => (int) $input['guests'],
    'notes' => isset($input['notes']) ? strip_tags(trim($input['notes'])) : '',
    'status' => 'pending',
    'createdAt' => date('c'),
    'completedAt' => null
);

$bookings[] = $newBooking;
writeBookings($bookings);

echo json_encode(array('success' => true, 'id' => $newBooking['id']));
