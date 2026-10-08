<?php require __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= SITE_NAME ?>｜<?= SITE_LOCATION ?>賞鯨豚預約</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <div>
            <h1><?= SITE_NAME ?></h1>
            <p><?= SITE_LOCATION ?>．賞鯨豚遊艇</p>
        </div>
        <nav class="header-nav">
            <?php if (!empty($_SESSION['admin_id'])): ?>
                <a href="admin/index.php" class="btn-login">前往後台管理</a>
            <?php else: ?>
                <a href="admin/login.php" class="btn-login">管理者登入</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">

    <section class="intro">
        <h2>關於我們</h2>
        <p>
            <?= SITE_NAME ?>從<?= SITE_LOCATION ?>出發，帶您出海欣賞龜山島與太平洋的鯨豚生態。
            全程專業船長與解說員陪同，提供安全舒適的賞鯨體驗。
        </p>
        <ul class="slot-list">
            <?php foreach (TIME_SLOTS as $label): ?>
                <li><?= htmlspecialchars($label) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="booking">
        <h2>線上預約</h2>
        <form id="booking-form">
            <div class="form-row">
                <label for="trip_date">出發日期</label>
                <input type="date" id="trip_date" name="trip_date" required>
            </div>

            <div class="form-row">
                <label for="time_slot">場次（參考時間）</label>
                <select id="time_slot" name="time_slot" required>
                    <option value="">請選擇場次</option>
                    <?php foreach (TIME_SLOTS as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#666;">實際當天開哪幾班以電話／現場確認為準。</small>
            </div>

            <div class="form-row split">
                <div>
                    <label for="adult_count">大人</label>
                    <input type="number" id="adult_count" name="adult_count" min="1" max="20" value="1" required>
                </div>
                <div>
                    <label for="child_count">小孩</label>
                    <input type="number" id="child_count" name="child_count" min="0" max="20" value="0">
                </div>
            </div>

            <div class="form-row">
                <label for="contact_name">聯絡人姓名</label>
                <input type="text" id="contact_name" name="contact_name" required>
            </div>

            <div class="form-row">
                <label for="phone">聯絡電話</label>
                <input type="tel" id="phone" name="phone" required>
            </div>

            <div class="form-row">
                <label for="email">Email（選填）</label>
                <input type="email" id="email" name="email">
            </div>

            <div class="form-row">
                <label for="notes">備註（選填）</label>
                <textarea id="notes" name="notes" rows="3"></textarea>
            </div>

            <button type="submit">送出預約</button>
            <p id="booking-result"></p>
        </form>
    </section>

    <section class="contact">
        <h2>聯絡資訊</h2>
        <p>電話：<?= htmlspecialchars(SITE_PHONE) ?>（備用 <?= htmlspecialchars(SITE_PHONE2) ?>）</p>
        <p>Line：<?= htmlspecialchars(SITE_LINE) ?></p>
        <p>Email：<?= htmlspecialchars(SITE_EMAIL) ?></p>
        <p>集合地點：<?= htmlspecialchars(SITE_ADDRESS) ?></p>
    </section>

</main>

<!-- 聊天機器人小工具 -->
<div id="chat-widget">
    <button id="chat-toggle" type="button" aria-label="開啟客服聊天">💬</button>
    <div id="chat-panel" class="hidden">
        <div class="chat-header">
            <span><?= SITE_NAME ?> 客服小幫手</span>
            <button id="chat-close" type="button" aria-label="關閉">×</button>
        </div>
        <div id="chat-log"></div>
        <form id="chat-form">
            <input type="text" id="chat-input" placeholder="輸入您的問題..." autocomplete="off">
            <button type="submit">送出</button>
        </form>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
