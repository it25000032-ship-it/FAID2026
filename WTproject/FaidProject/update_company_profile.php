<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function companyProfileUpdateResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    companyProfileUpdateResponse(false, "Invalid request method.", 405);
}
if (empty($_SESSION["company_id"]) || !empty($_SESSION["admin_id"])) {
    companyProfileUpdateResponse(false, "Please sign in as this company to edit its profile.", 401);
}
require_csrf_token(true);

$companyId = filter_var($_SESSION["company_id"], FILTER_VALIDATE_INT);
$name = trim(post_string("company_name"));
$phone = trim(post_string("phone"));
$address = trim(post_string("address"));
$description = trim(post_string("description"));

if (
    !$companyId ||
    $name === "" ||
    mb_strlen($name, "UTF-8") > 150 ||
    $phone === "" ||
    mb_strlen($phone, "UTF-8") > 30 ||
    $address === "" ||
    mb_strlen($address, "UTF-8") > 255 ||
    mb_strlen($description, "UTF-8") > 500
) {
    companyProfileUpdateResponse(false, "Enter valid profile details. The description may be up to 500 characters.", 400);
}

$stmt = $conn->prepare(
    "UPDATE companies
     SET company_name = ?, phone = ?, address = ?, description = ?
     WHERE company_id = ?"
);
if (!$stmt) {
    error_log("Company profile update prepare failed: " . $conn->error);
    companyProfileUpdateResponse(false, "Could not update the company profile.", 500);
}
$stmt->bind_param("ssssi", $name, $phone, $address, $description, $companyId);
if (!$stmt->execute()) {
    error_log("Company profile update failed: " . $stmt->error);
    $stmt->close();
    companyProfileUpdateResponse(false, "Could not update the company profile.", 500);
}
$stmt->close();
$conn->close();

$_SESSION["companyName"] = $name;
companyProfileUpdateResponse(true, "Company profile updated successfully.");
?>
