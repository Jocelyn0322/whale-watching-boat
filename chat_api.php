<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => '不支援的請求方式'], 405);
}

$input = json_input();
$message = trim((string)($input['message'] ?? ''));

if ($message === '') {
    json_response(['ok' => false, 'error' => '請輸入訊息內容'], 422);
}
if (mb_strlen($message) > 1000) {
    json_response(['ok' => false, 'error' => '訊息過長'], 422);
}

if (!isset($_SESSION['chat_history']) || !is_array($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

$_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
// 只保留最近 10 則訊息，避免對話無限增長
$_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);

$reply = call_claude($_SESSION['chat_history']);

if ($reply === null) {
    $reply = fallback_reply($message);
}

$_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
$_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);

json_response(['ok' => true, 'reply' => $reply]);

/**
 * 呼叫 Claude Messages API。若未設定 ANTHROPIC_API_KEY 或呼叫失敗，回傳 null 交由呼叫端使用備援問答。
 */
function call_claude(array $history): ?string
{
    $apiKey = getenv('ANTHROPIC_API_KEY');
    if (!$apiKey) {
        return null;
    }

    $systemPrompt =
          "你是「" . SITE_NAME . "」的客服聊天機器人，公司位於" . SITE_LOCATION . "，提供賞鯨豚遊艇行程。\n\n"
        . "【可回答的營運資訊】\n"
        . "- 集合地點：" . SITE_ADDRESS . "\n"
        . "- 電話：" . SITE_PHONE . "（備用 " . SITE_PHONE2 . "）；Line：" . SITE_LINE . "；Email：" . SITE_EMAIL . "\n"
        . "- 參考出航時間：08:00、10:30、13:00、15:30（實際當天開哪幾班以電話／現場確認為準，請據實告知顧客這點）。\n"
        . "- 票價方案：\n"
        . "  ‧ 基礎方案：航程約 3 小時，大人 1,200 元、兒童(3–12歲) 800 元、長者 300 元。\n"
        . "  ‧ 進階方案：航程約 4.5 小時，大人 1,600 元、兒童 1,200 元。\n"
        . "  ‧ 組合方案：大人 1,200 元、兒童 800 元。\n"
        . "  ‧ 兒童專案：大人 600 元、兒童 500 元。\n"
        . "- 搭乘限制：需年滿 3 歲以上，並請顧客留意自身身體狀況（如孕婦、心血管疾病者建議先諮詢）。\n"
        . "- 天候政策：若因天候不佳影響安全，可能取消或改期，會主動聯繫顧客。\n\n"
        . "【回答規則】\n"
        . "- 一律使用繁體中文，語氣親切簡潔，每則回覆 2 到 4 句即可。\n"
        . "- 只根據上面的資訊回答；不確定或資訊沒有涵蓋的（例如特定日期是否開船、退款細節、客訴），請引導顧客撥打 " . SITE_PHONE . " 或加 Line " . SITE_LINE . " 詢問，不要自行編造。\n"
        . "- 若顧客想預約，請引導他們使用網頁上方的「線上預約」表單填寫，不需要在對話中蒐集個人資料。";

    $payload = [
        'model' => ANTHROPIC_MODEL,
        'max_tokens' => 512,
        'system' => $systemPrompt,
        'messages' => array_map(
            static fn($m) => ['role' => $m['role'], 'content' => $m['content']],
            $history
        ),
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log('Claude API 呼叫失敗: HTTP ' . $httpCode . ' ' . $curlError . ' ' . $response);
        return null;
    }

    $data = json_decode($response, true);
    if (($data['stop_reason'] ?? null) === 'refusal') {
        return '這個問題我無法回答，建議您直接撥打電話 ' . SITE_PHONE . ' 與我們聯繫。';
    }
    foreach ($data['content'] ?? [] as $block) {
        if (($block['type'] ?? null) === 'text') {
            return $block['text'];
        }
    }
    return null;
}

/**
 * 未設定 API 金鑰時的簡易關鍵字問答，讓雛形在沒有 LLM 金鑰的情況下也能示範互動。
 */
function fallback_reply(string $message): string
{
    $m = mb_strtolower($message);

    $rules = [
        ['keywords' => ['價格', '價錢', '費用', '多少錢', '票價', '收費'], 'reply' =>
            "票價方案如下：\n・基礎方案(約3小時)：大人1,200、兒童(3-12歲)800、長者300\n・進階方案(約4.5小時)：大人1,600、兒童1,200\n・組合方案：大人1,200、兒童800\n・兒童專案：大人600、兒童500\n實際方案可電話 " . SITE_PHONE . " 確認。"],
        ['keywords' => ['時間', '場次', '幾點', '班次', '出發'], 'reply' => '參考出航時間為 ' . implode('、', array_values(TIME_SLOTS)) . '，實際當天開哪幾班以電話或現場確認為準。'],
        ['keywords' => ['預約', '訂位', '怎麼約', '報名'], 'reply' => '請直接使用網頁上方的「線上預約」表單，填寫日期、場次與聯絡資料即可完成預約。'],
        ['keywords' => ['天氣', '風浪', '取消', '改期', '退'], 'reply' => '若遇天候不佳影響出航安全，我們會主動聯繫您改期或退費，請保持電話暢通。'],
        ['keywords' => ['電話', '聯絡', '地址', '地點', 'line', '信箱', 'email'], 'reply' =>
            '電話：' . SITE_PHONE . '（備用 ' . SITE_PHONE2 . '）\nLine：' . SITE_LINE . '\nEmail：' . SITE_EMAIL . "\n集合地點：" . SITE_ADDRESS],
        ['keywords' => ['小孩', '兒童', '孕婦', '年齡', '幾歲', '長者', '老人'], 'reply' => '需年滿 3 歲以上方可參加；孕婦或有心血管疾病者建議先諮詢，並留意自身身體狀況。'],
    ];

    foreach ($rules as $rule) {
        foreach ($rule['keywords'] as $kw) {
            if (mb_strpos($m, $kw) !== false) {
                return $rule['reply'];
            }
        }
    }

    return '感謝您的訊息！若想預約行程，請填寫上方表單；若有其他問題，也可以直接撥打 ' . SITE_PHONE . ' 詢問。';
}
