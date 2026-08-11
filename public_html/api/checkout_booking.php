<?php
require_once __DIR__ . '/_helpers.php';

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

checkApiKey();
$input = getJsonBody();

$id = isset($input['id']) ? $input['id'] : null;
if (!$id) {
    jsonError('ID booking wajib diisi.', 400);
}

$bookings = readBookings();
$found = false;

foreach ($bookings as &$b) {
    if (isset($b['id']) && $b['id'] === $id) {
        $b['status'] = 'selesai';
        $b['completedAt'] = date('c');
        $found = true;
        break;
    }
}
unset($b);

if (!$found) {
    jsonError('Booking tidak ditemukan.', 404);
}

writeBookings($bookings);
echo json_encode(array('success' => true));
