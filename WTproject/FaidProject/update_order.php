
<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

$id = (int)($_POST["order_id"] ?? 0);
$status = $_POST["status"] ?? "";

$allowedStatuses = ["Pending", "Approved", "Rejected", "Completed"];

if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    $id <= 0 ||
    !in_array($status, $allowedStatuses, true)
) {
    die("Invalid order information.");
}

$stmt = $conn->prepare(
    "UPDATE orders SET status = ? WHERE order_id = ?"
);
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    header("Location: ../admin.php#orders");
    exit();
}

die("Could not update order: " . $stmt->error);
