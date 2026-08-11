<?php
define('API_KEY', '40deb31a-eeec-4fe6-82b9-dd9493a4ec3d');
define('TOTAL_TABLES', 15);
define('DATA_DIR', __DIR__ . '/../data');
define('DATA_FILE', DATA_DIR . '/bookings.json');

header('Content-Type: application/json; charset=utf-8');

function jsonError($message, $status = 500) {
    http_response_code($status);
    echo json_encode(array('success' => false, 'message' => $message));
    exit;
}

function ensureDataFile() {
    if (!is_dir(DATA_DIR)) {
        if (!@mkdir(DATA_DIR, 0755, true) && !is_dir(DATA_DIR)) {
            jsonError('Folder data tidak ditemukan dan gagal dibuat. Buat folder /data dan pastikan writable.', 500);
        }
    }

    if (!file_exists(DATA_FILE)) {
        if (@file_put_contents(DATA_FILE, '[]') === false) {
            jsonError('bookings.json tidak ditemukan dan gagal dibuat. Pastikan folder /data writable.', 500);
        }
    }

    if (!is_readable(DATA_FILE)) {
        jsonError('bookings.json tidak dapat dibaca oleh server.', 500);
    }
}

function getJsonBody() {
    static $body = null;
    if ($body === null) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        $body = is_array($decoded) ? $decoded : array();
    }
    return $body;
}

function checkApiKey() {
    $key = isset($_GET['key']) ? $_GET['key'] : null;

    if ($key === null && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = getJsonBody();
        $key = isset($body['key']) ? $body['key'] : null;
    }

    if ($key !== API_KEY) {
        jsonError('API key tidak valid.', 401);
    }
}

function readBookings() {
    ensureDataFile();

    $fp = @fopen(DATA_FILE, 'r');
    if (!$fp) {
        jsonError('Gagal membuka bookings.json untuk dibaca.', 500);
    }

    if (!flock($fp, LOCK_SH)) {
        fclose($fp);
        jsonError('Gagal mengunci bookings.json untuk dibaca.', 500);
    }

    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($content, true);
    return is_array($data) ? $data : array();
}

function writeBookings($bookings) {
    ensureDataFile();

    $fp = @fopen(DATA_FILE, 'c+');
    if (!$fp) {
        jsonError('Gagal membuka bookings.json untuk ditulis. Pastikan folder /data writable.', 500);
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        jsonError('Gagal mengunci bookings.json untuk ditulis.', 500);
    }

    ftruncate($fp, 0);
    rewind($fp);

    $json = json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false || fwrite($fp, $json) === false) {
        flock($fp, LOCK_UN);
        fclose($fp);
        jsonError('Gagal menyimpan data reservasi.', 500);
    }

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
