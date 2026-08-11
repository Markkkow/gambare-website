<?php
/* ════════════════════════════════════════════════
   checkout_booking.php — tandai reservasi selesai (checkout)
   Dipanggil dari kasir.html
═══════════════════════════════════════════════════ */
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

checkApiKey();

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID booking wajib diisi.']);
    exit;
}

$bookings = readBookings();
$found = false;

foreach ($bookings as &$b) {
    if ($b['id'] === $id) {
        $b['status']      = 'selesai';
        $b['completedAt'] = date('c');
        $found = true;
        break;
    }
}
unset($b);

if (!$found) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan.']);
    exit;
}

writeBookings($bookings);
echo json_encode(['success' => true]);
