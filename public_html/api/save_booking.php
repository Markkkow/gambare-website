<?php
/* ════════════════════════════════════════════════
   save_booking.php — simpan reservasi baru
   Dipanggil dari index.html saat customer klik
   "Buka WhatsApp & Kirim"
═══════════════════════════════════════════════════ */
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

checkApiKey();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
    exit;
}

// ── Validasi field wajib ──
$required = ['name', 'phone', 'date', 'time', 'floor', 'tables', 'guests'];
foreach ($required as $field) {
    if (empty($input[$field]) && $input[$field] !== 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' wajib diisi."]);
        exit;
    }
}
if (!isValidDate($input['date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid.']);
    exit;
}

$tables = (int) $input['tables'];
if ($tables < 1 || $tables > TOTAL_TABLES) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Jumlah meja tidak valid.']);
    exit;
}

// ── Cek sisa meja untuk tanggal itu (mencegah overbooking) ──
$bookings = readBookings();
$bookedToday = 0;
foreach ($bookings as $b) {
    if ($b['date'] === $input['date'] && $b['status'] === 'pending') {
        $bookedToday += (int) $b['tables'];
    }
}
if ($bookedToday + $tables > TOTAL_TABLES) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Meja tidak cukup. Sisa: ' . (TOTAL_TABLES - $bookedToday)
    ]);
    exit;
}

// ── Simpan booking baru ──
$newBooking = [
    'id'          => uniqid('bk_', true),
    'name'        => strip_tags(trim($input['name'])),
    'phone'       => preg_replace('/[^0-9+]/', '', $input['phone']),
    'date'        => $input['date'],
    'time'        => $input['time'],
    'floor'       => strip_tags(trim($input['floor'])),
    'tables'      => $tables,
    'guests'      => (int) $input['guests'],
    'notes'       => isset($input['notes']) ? strip_tags(trim($input['notes'])) : '',
    'status'      => 'pending',
    'createdAt'   => date('c'),
    'completedAt' => null,
];

$bookings[] = $newBooking;
writeBookings($bookings);

echo json_encode(['success' => true, 'id' => $newBooking['id']]);
