<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#orders");
    exit();
}
require_csrf_token();

$orderId = filter_var(post_string("order_id"), FILTER_VALIDATE_INT);
if (!$orderId || $orderId < 1) {
    http_response_code(400);
    exit("Invalid order ID.");
}
if (!$conn->begin_transaction()) {
    http_response_code(500);
    exit("Could not delete the order. Please try again.");
}

$stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log("Order items delete prepare failed: " . $conn->error);
    http_response_code(500);
    exit("Could not delete the order. Please try again.");
}
$stmt->bind_param("i", $orderId);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log("Order items delete failed: " . $stmt->error);
    http_response_code(500);
    exit("Could not delete the order. Please try again.");
}
$stmt->close();

$stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log("Order delete prepare failed: " . $conn->error);
    http_response_code(500);
    exit("Could not delete the order. Please try again.");
}
$stmt->bind_param("i", $orderId);
if (!$stmt->execute() || $stmt->affected_rows !== 1) {
    $conn->rollback();
    error_log("Order delete failed: " . $stmt->error);
    $stmt->close();
    http_response_code(404);
    exit("Order not found or could not be deleted.");
}
$stmt->close();
if (!$conn->commit()) {
    $conn->rollback();
    http_response_code(500);
    exit("Could not delete the order. Please try again.");
}
header("Location: ../admin.php#orders");
exit();
?>
