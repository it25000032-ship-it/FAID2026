<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function companyEditResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    companyEditResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$id = filter_var(post_string("company_id"), FILTER_VALIDATE_INT);
$name = trim(post_string("company_name"));
$email = trim(post_string("email"));
$phone = trim(post_string("phone"));
$address = trim(post_string("address"));
$description = trim(post_string("description"));
if (
    !$id ||
    $id < 1 ||
    $name === "" ||
    strlen($name) > 150 ||
    strlen($email) > 150 ||
    strlen($phone) > 30 ||
    strlen($address) > 255 ||
    mb_strlen($description, "UTF-8") > 500 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    companyEditResponse(false, "Please enter valid company information.", 400);
}

$stmt = $conn->prepare(
    "UPDATE companies
     SET company_name = ?, email = ?, phone = ?, address = ?, description = ?
     WHERE company_id = ?"
);
if (!$stmt) {
    error_log("Company update prepare failed: " . $conn->error);
    companyEditResponse(false, "Could not update the company. Please try again.", 500);
}
$stmt->bind_param("sssssi", $name, $email, $phone, $address, $description, $id);
if (!$stmt->execute()) {
    $duplicateEmail = $stmt->errno === 1062;
    error_log("Company update failed: " . $stmt->error);
    $stmt->close();
    companyEditResponse(
        false,
        $duplicateEmail ? "That email belongs to another company." : "Could not update the company. Please try again.",
        $duplicateEmail ? 409 : 500
    );
}
$stmt->close();
$conn->close();
companyEditResponse(true, "Company updated successfully.");
?>
