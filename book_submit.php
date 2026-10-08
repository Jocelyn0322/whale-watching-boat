<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => '不支援的請求方式'], 405);
}

$input = json_input();

$tripDate   = trim((string)($input['trip_date'] ?? ''));
$timeSlot   = trim((string)($input['time_slot'] ?? ''));
$adultCount = (int)($input['adult_count'] ?? 0);
$childCount = (int)($input['child_count'] ?? 0);
$name       = trim((string)($input['contact_name'] ?? ''));
$phone      = trim((string)($input['phone'] ?? ''));
$email      = trim((string)($input['email'] ?? ''));
$notes      = trim((string)($input['notes'] ?? ''));

// ---- 基本驗證 ----
$errors = [];

$dateObj = DateTime::createFromFormat('Y-m-d', $tripDate);
if (!$dateObj || $dateObj->format('Y-m-d') !== $tripDate) {
    $errors[] = '出發日期格式錯誤';
} elseif ($dateObj < new DateTime('today')) {
    $errors[] = '出發日期不可為過去日期';
}

if (!array_key_exists($timeSlot, TIME_SLOTS)) {
    $errors[] = '請選擇有效的場次';
}

if ($adultCount < 1 || $adultCount > 20) {
    $errors[] = '大人人數需介於 1 到 20 之間';
}
if ($childCount < 0 || $childCount > 20) {
    $errors[] = '小孩人數不可為負數';
}

if ($name === '') {
    $errors[] = '請輸入聯絡人姓名';
}
if (!preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
    $errors[] = '請輸入有效的聯絡電話';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '請輸入有效的 Email';
}

if ($errors) {
    json_response(['ok' => false, 'error' => implode('；', $errors)], 422);
}

$pdo = db();

// ---- 檢查該場次剩餘座位 ----
$stmt = $pdo->prepare(
    'SELECT COALESCE(SUM(adult_count + child_count), 0) AS taken
     FROM bookings
     WHERE trip_date = :trip_date AND time_slot = :time_slot AND status = "confirmed"'
);
$stmt->execute(['trip_date' => $tripDate, 'time_slot' => $timeSlot]);
$taken = (int)$stmt->fetchColumn();
$requested = $adultCount + $childCount;

if ($taken + $requested > SLOT_CAPACITY) {
    $remaining = max(0, SLOT_CAPACITY - $taken);
    json_response([
        'ok' => false,
        'error' => "該場次剩餘名額不足（僅剩 {$remaining} 位），請選擇其他場次或日期",
    ], 422);
}

// ---- 寫入預約（原型階段：送出即自動確認）----
$stmt = $pdo->prepare(
    'INSERT INTO bookings (trip_date, time_slot, adult_count, child_count, contact_name, phone, email, notes, status)
     VALUES (:trip_date, :time_slot, :adult_count, :child_count, :contact_name, :phone, :email, :notes, "confirmed")'
);
$stmt->execute([
    'trip_date'    => $tripDate,
    'time_slot'    => $timeSlot,
    'adult_count'  => $adultCount,
    'child_count'  => $childCount,
    'contact_name' => $name,
    'phone'        => $phone,
    'email'        => $email !== '' ? $email : null,
    'notes'        => $notes !== '' ? $notes : null,
]);

json_response([
    'ok' => true,
    'booking_id' => (int)$pdo->lastInsertId(),
    'message' => '預約成功！我們已收到您的資料，若有問題將主動與您聯繫。',
]);
