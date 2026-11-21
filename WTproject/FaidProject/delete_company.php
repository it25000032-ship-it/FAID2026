<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#companies");
    exit();
}
require_csrf_token();

$companyId = filter_var(post_string("company_id"), FILTER_VALIDATE_INT);
if (!$companyId || $companyId < 1) {
    http_response_code(400);
    exit("Invalid company ID.");
}
if (!$conn->begin_transaction()) {
    http_response_code(500);
    exit("Could not delete the company. Please try again.");
}

$stmt = $conn->prepare(
    "SELECT
        (SELECT COUNT(*) FROM materials WHERE company_id = ?) AS material_count,
        (SELECT COUNT(*) FROM orders WHERE buyer_id = ?) AS order_count"
);
if (!$stmt) {
    $conn->rollback();
    error_log("Company dependency lookup prepare failed: " . $conn->error);
    http_response_code(500);
    exit("Could not delete the company. Please try again.");
}
$stmt->bind_param("ii", $companyId, $companyId);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log("Company dependency lookup failed: " . $stmt->error);
    http_response_code(500);
    exit("Could not delete the company. Please try again.");
}
$dependencies = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ((int)$dependencies["material_count"] > 0 || (int)$dependencies["order_count"] > 0) {
    $conn->rollback();
    http_response_code(409);
    exit("This company has offers or orders. Remove or reassign those records first.");
}

$stmt = $conn->prepare("DELETE FROM companies WHERE company_id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log("Company delete prepare failed: " . $conn->error);
    http_response_code(500);
    exit("Could not delete the company. Please try again.");
}
$stmt->bind_param("i", $companyId);
if (!$stmt->execute() || $stmt->affected_rows !== 1) {
    $conn->rollback();
    error_log("Company delete failed: " . $stmt->error);
    $stmt->close();
    http_response_code(404);
    exit("Company not found or could not be deleted.");
}
$stmt->close();
if (!$conn->commit()) {
    $conn->rollback();
    http_response_code(500);
    exit("Could not delete the company. Please try again.");
}
header("Location: ../admin.php#companies");
exit();
?>
