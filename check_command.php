<?php
// [مهم] مسیر پوشه دستورات با اسلش انتهایی
define('COMMAND_DIR', '/home/booksqui/command_files/'); // <<-- مسیر شما

// --- تنظیم هدر خروجی به عنوان JSON ---
header('Content-Type: application/json; charset=utf-8'); // Specify UTF-8

// --- دریافت شناسه جلسه از درخواست AJAX (POST) ---
// Sanitize session ID strictly
$sessionId = isset($_POST['session_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['session_id']) : null;

// --- بررسی وجود شناسه جلسه معتبر ---
if (empty($sessionId)) { // Use empty() to check for null, false, empty string, '0'
    error_log("check_command: Session ID missing or invalid in request.");
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Session ID missing or invalid.']);
    exit;
}

error_log("check_command: Checking command for session ID: " . $sessionId);

// --- ساخت مسیر کامل فایل دستور ---
$commandFilePath = rtrim(COMMAND_DIR, '/') . '/' . $sessionId . '.command';

// --- متغیرها برای پاسخ ---
$command = null;
$response = ['status' => 'not_found']; // Default response: command not found

// --- بررسی وجود فایل دستور ---
if (file_exists($commandFilePath)) {
    error_log("check_command: Command file exists: " . $commandFilePath);

    // Attempt to read file content
    $commandContent = @trim(file_get_contents($commandFilePath));

    if ($commandContent !== false && !empty($commandContent)) {
        // File read successfully and is not empty
        $command = $commandContent;
        error_log("check_command: Found command '{$command}' in file: " . $commandFilePath);

        // Attempt to delete the file after reading
        if (@unlink($commandFilePath)) {
            error_log("check_command: Successfully deleted command file: " . $commandFilePath);
            $response = ['status' => 'found', 'command' => $command];
        } else {
            // Failed to delete file (e.g., permissions issue)
            error_log("check_command: FAILED to delete command file (permissions?): " . $commandFilePath);
            // Return the command anyway, but include a warning
            $response = ['status' => 'found', 'command' => $command, 'warning' => 'Failed to delete command file after reading.'];
        }
    } else {
        // File existed but was empty or could not be read
        error_log("check_command: Command file existed but content was empty or unreadable: " . $commandFilePath);
        @unlink($commandFilePath); // Attempt to delete the problematic file
        $response = ['status' => 'error', 'message' => 'Command file was empty or unreadable.'];
        http_response_code(500); // Internal Server Error might be appropriate
    }
} else {
     // File does not exist - normal case during polling
     // error_log("check_command: No command file found for session ID: " . $sessionId . " at path: " . $commandFilePath);
     // Keep the default response: ['status' => 'not_found']
}

// --- ارسال پاسخ نهایی به صورت JSON ---
// error_log("check_command: Sending response for session [{$sessionId}]: " . json_encode($response)); // Optional: Log response
echo json_encode($response);

exit; // Ensure script terminates cleanly
?>