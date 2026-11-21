<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function sendResponse($success, $message, $extra = [], $status = 200)
{
    http_response_code($status);
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(false, "Invalid request method.", [], 405);
}
require_csrf_token(true);

$email = trim(post_string("email"));
$password = post_string("password");
if ($email === "" || $password === "") {
    sendResponse(false, "Please fill in all fields.", [], 400);
}

$stmt = $conn->prepare(
    "SELECT company_id, company_name, password_hash FROM companies WHERE email = ?"
);
if (!$stmt) {
    error_log("Company login prepare failed: " . $conn->error);
    sendResponse(false, "Login failed. Please try again.", [], 500);
}

$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    error_log("Company login query failed: " . $stmt->error);
    $stmt->close();
    sendResponse(false, "Login failed. Please try again.", [], 500);
}

$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row || !password_verify($password, $row["password_hash"])) {
    sendResponse(false, "Invalid email or password.", [], 401);
}

session_regenerate_id(true);
unset($_SESSION["admin_id"], $_SESSION["admin_name"]);
$_SESSION["user_id"] = $row["company_id"];
$_SESSION["company_id"] = $row["company_id"];
$_SESSION["companyName"] = $row["company_name"];
$token = csrf_rotate_token();

sendResponse(true, "Login successful! Welcome " . $row["company_name"], [
    "company_id" => (int)$row["company_id"],
    "csrf_token" => $token
]);
?>