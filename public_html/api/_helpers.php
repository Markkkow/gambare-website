<?php
/* ════════════════════════════════════════════════
   _helpers.php — fungsi bersama untuk semua endpoint API
   Gambare House Reservation System (PHP + JSON file)
═══════════════════════════════════════════════════ */

// ── KONFIGURASI ──
// PENTING: ganti API_KEY ini, dan pastikan SAMA PERSIS
// dengan nilai API_KEY di file config.js
define('API_KEY', '40deb31a-eeec-4fe6-82b9-dd9493a4ec3d');
define('TOTAL_TABLES', 15);
define('DATA_FILE', __DIR__ . '/../data/bookings.json');

header('Content-Type: application/json; charset=utf-8');

// ── VALIDASI API KEY ──
// Dicek dari query string (?key=) untuk GET, atau dari body JSON untuk POST
function checkApiKey() {
    $key = $_GET['key'] ?? null;

    if ($key === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $key  = $body['key'] ?? null;
    }

    if ($key !== API_KEY) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'API key tidak valid.']);
        exit;
    }
}

// ── BACA data booking (dengan file lock supaya aman kalau diakses bersamaan) ──
function readBookings() {
    if (!file_exists(DATA_FILE)) {
        file_put_contents(DATA_FILE, '[]');
        return [];
    }
    $fp = fopen(DATA_FILE, 'r');
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// ── TULIS data booking (dengan exclusive lock) ──
function writeBookings($bookings) {
    $fp = fopen(DATA_FILE, 'c');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
}

// ── Validasi format tanggal YYYY-MM-DD sederhana ──
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
