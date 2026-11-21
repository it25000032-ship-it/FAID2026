<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function companyAddResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    companyAddResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$companyName = trim(post_string("company_name"));
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
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $password === "" ||
    strlen($password) < 8 ||
    strlen($password) > 1024
) {
    companyAddResponse(false, "Enter valid company details and a password of 8 to 1024 characters.", 400);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare(
    "INSERT INTO companies (company_name, email, phone, address, password_hash)
     VALUES (?, ?, ?, ?, ?)"
);
if (!$stmt) {
    error_log("Admin company insert prepare failed: " . $conn->error);
    companyAddResponse(false, "Could not add the company. Please try again.", 500);
}
$stmt->bind_param("sssss", $companyName, $email, $phone, $address, $passwordHash);
if (!$stmt->execute()) {
    $duplicateEmail = $stmt->errno === 1062;
    error_log("Admin company insert failed: " . $stmt->error);
    $stmt->close();
    companyAddResponse(
        false,
        $duplicateEmail ? "This email is already registered." : "Could not add the company. Please try again.",
        $duplicateEmail ? 409 : 500
    );
}

$stmt->close();
$conn->close();
companyAddResponse(true, "Company added successfully.");
?>
