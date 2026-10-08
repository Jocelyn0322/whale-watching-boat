<?php
declare(strict_types=1);

session_start();
date_default_timezone_set('Asia/Taipei');

// ---- 資料庫設定 ----
// 這台機器的 XAMPP MySQL 使用 3307 連接埠且 root 有設密碼（與同一台機器上
// 其他專案 New-SA/db.php 使用的設定一致）。若換到別台機器部署，通常改回
// 3306、密碼留空即可。
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'dongyang_mingzhu');
define('DB_USER', 'root');
define('DB_PASS', '6Pd?#fZyb0gl');

// ---- 網站基本資訊 ----
define('SITE_NAME', '東陽明珠');
define('SITE_LOCATION', '宜蘭頭城');
define('SITE_PHONE', '0986-828627');       // 主要電話
define('SITE_PHONE2', '0970-092075');      // 備用電話
define('SITE_LINE', '@286qshfa');
define('SITE_EMAIL', 'whale.world@yahoo.com.tw');
define('SITE_ADDRESS', '宜蘭縣頭城鎮168號');

// ---- 賞鯨船班次 ----
// 這些是「參考」出航時間，實際當天開哪幾班仍以電話／現場確認為準。
define('TIME_SLOTS', [
    's0800' => '08:00',
    's1030' => '10:30',
    's1300' => '13:00',
    's1530' => '15:30',
]);

// 每個場次的總座位數上限（用於檢查是否額滿）
define('SLOT_CAPACITY', 30);

// ---- Claude API 設定（聊天機器人）----
// 於系統環境變數設定 ANTHROPIC_API_KEY 後即可啟用真正的 LLM 回覆，
// 若未設定，聊天機器人會退回使用內建的關鍵字問答。
define('ANTHROPIC_MODEL', 'claude-opus-4-8');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
