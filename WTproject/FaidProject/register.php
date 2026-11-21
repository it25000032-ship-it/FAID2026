<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function sendResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$companyName = trim(post_string("companyName"));
$email = trim(post_string("email"));
$phone = trim(post_string("phone"));
$address = trim(post_string("address"));
$password = post_string("password");

if (
    $companyName === "" ||
    strlen($companyName) > 150 ||
    strlen($email) > 150 ||
    strlen($phone) > 30 ||
    strlen($address) > 255 ||
    $email === "" ||
    $phone === "" ||
    $address === "" ||
    $password === "" ||
    strlen($password) > 1024
) {
    sendResponse(false, "Please fill in all fields.", 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, "Invalid email address.", 400);
}
if (strlen($password) < 8 || strlen($password) > 1024) {
    sendResponse(false, "Password must be between 8 and 1024 characters.", 400);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare(
    "INSERT INTO companies (company_name, email, phone, address, password_hash)
     VALUES (?, ?, ?, ?, ?)"
);
if (!$stmt) {
    error_log("Company registration prepare failed: " . $conn->error);
    sendResponse(false, "Registration failed. Please try again.", 500);
}

$stmt->bind_param("sssss", $companyName, $email, $phone, $address, $passwordHash);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendResponse(true, "Registration successful! Please sign in.");
}

$duplicateEmail = $stmt->errno === 1062;
error_log("Company registration failed: " . $stmt->error);
$stmt->close();
$conn->close();
sendResponse(
    false,
    $duplicateEmail ? "This email is already registered." : "Registration failed. Please try again.",
    $duplicateEmail ? 409 : 500
);
?>