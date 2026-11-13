
<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

$id = (int)($_POST["order_id"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || $id <= 0) {
    header("Location: ../admin.php#orders");
    exit();
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "DELETE FROM order_items WHERE order_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        "DELETE FROM orders WHERE order_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Order not found.");
    }

    $stmt->close();
    $conn->commit();

    header("Location: ../admin.php#orders");
    exit();

} catch (Throwable $e) {
    $conn->rollback();
    die("Could not delete order: " . $e->getMessage());
}
