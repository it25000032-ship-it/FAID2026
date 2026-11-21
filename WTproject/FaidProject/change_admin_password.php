<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function passwordChangeResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    passwordChangeResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$currentPassword = post_string("current_password");
$newPassword = post_string("new_password");
$confirmPassword = post_string("confirm_password");
if ($currentPassword === "" || strlen($newPassword) < 8 || strlen($newPassword) > 1024) {
    passwordChangeResponse(false, "Enter your current password and a new password of 8 to 1024 characters.", 400);
}
if ($newPassword !== $confirmPassword) {
    passwordChangeResponse(false, "The new password confirmation does not match.", 400);
}

$adminId = (int)$_SESSION["admin_id"];
$stmt = $conn->prepare("SELECT password_hash FROM admins WHERE admin_id = ?");
if (!$stmt) {
    error_log("Admin password lookup prepare failed: " . $conn->error);
    passwordChangeResponse(false, "Could not change the password. Please try again.", 500);
}
$stmt->bind_param("i", $adminId);
if (!$stmt->execute()) {
    error_log("Admin password lookup failed: " . $stmt->error);
    passwordChangeResponse(false, "Could not change the password. Please try again.", 500);
}
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    passwordChangeResponse(false, "Administrator account not found.", 404);
}

$storedPassword = (string)$row["password_hash"];
$passwordInfo = password_get_info($storedPassword);
$hasPasswordHash = $passwordInfo["algo"] !== null && $passwordInfo["algo"] !== 0;
$currentPasswordValid = password_verify($currentPassword, $storedPassword) ||
    (!$hasPasswordHash && hash_equals($storedPassword, $currentPassword));
if (!$currentPasswordValid) {
    passwordChangeResponse(false, "Current password is incorrect.", 401);
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE admin_id = ?");
if (!$stmt) {
    error_log("Admin password update prepare failed: " . $conn->error);
    passwordChangeResponse(false, "Could not change the password. Please try again.", 500);
}
$stmt->bind_param("si", $newHash, $adminId);
if (!$stmt->execute()) {
    error_log("Admin password update failed: " . $stmt->error);
    passwordChangeResponse(false, "Could not change the password. Please try again.", 500);
}
$stmt->close();
$conn->close();
passwordChangeResponse(true, "Administrator password updated successfully.");
?>
