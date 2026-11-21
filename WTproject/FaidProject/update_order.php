<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function updateOrderResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    updateOrderResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$orderId = filter_var(post_string("order_id"), FILTER_VALIDATE_INT);
$newStatus = post_string("status");
$validStatuses = ["Pending", "Approved", "Rejected", "Completed"];
if (!$orderId || $orderId < 1 || !in_array($newStatus, $validStatuses, true)) {
    updateOrderResponse(false, "Invalid order information.", 400);
}

if (!$conn->begin_transaction()) {
    updateOrderResponse(false, "Could not update the order. Please try again.", 500);
}

try {
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? FOR UPDATE");
    if (!$stmt) {
        throw new RuntimeException("Order lookup prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $orderId);
    if (!$stmt->execute()) {
        throw new RuntimeException("Order lookup failed: " . $stmt->error);
    }
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        $conn->rollback();
        updateOrderResponse(false, "Order not found.", 404);
    }

    $currentStatus = $order["status"];
    if ($currentStatus === $newStatus) {
        $conn->commit();
        updateOrderResponse(true, "Order status is unchanged.");
    }

    $allowedTransitions = [
        "Pending" => ["Approved", "Rejected", "Completed"],
        "Approved" => ["Rejected", "Completed"],
        "Rejected" => [],
        "Completed" => []
    ];
    if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
        $conn->rollback();
        updateOrderResponse(false, "That order status transition is not allowed.", 409);
    }

    if ($newStatus === "Completed") {
        $stmt = $conn->prepare(
            "SELECT material_id, quantity
             FROM order_items
             WHERE order_id = ?
             ORDER BY material_id"
        );
        if (!$stmt) {
            throw new RuntimeException("Order items prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $orderId);
        if (!$stmt->execute()) {
            throw new RuntimeException("Order items lookup failed: " . $stmt->error);
        }
        $itemsResult = $stmt->get_result();
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
        $stmt->close();

        foreach ($items as $item) {
            $materialId = (int)$item["material_id"];
            $orderedQuantity = (int)$item["quantity"];

            $stmt = $conn->prepare("SELECT quantity FROM materials WHERE material_id = ? FOR UPDATE");
            if (!$stmt) {
                throw new RuntimeException("Stock lookup prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $materialId);
            if (!$stmt->execute()) {
                throw new RuntimeException("Stock lookup failed: " . $stmt->error);
            }
            $stock = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$stock) {
                throw new RuntimeException("An order material no longer exists.");
            }

            $stmt = $conn->prepare(
                "SELECT COALESCE(SUM(oi.quantity), 0) AS reserved
                 FROM order_items oi
                 INNER JOIN orders o ON o.order_id = oi.order_id
                 WHERE oi.material_id = ?
                   AND oi.order_id <> ?
                   AND o.status IN ('Pending', 'Approved')"
            );
            if (!$stmt) {
                throw new RuntimeException("Reservation lookup prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $materialId, $orderId);
            if (!$stmt->execute()) {
                throw new RuntimeException("Reservation lookup failed: " . $stmt->error);
            }
            $otherReserved = (int)$stmt->get_result()->fetch_assoc()["reserved"];
            $stmt->close();

            if ((int)$stock["quantity"] - $orderedQuantity < $otherReserved) {
                $conn->rollback();
                updateOrderResponse(false, "Stock reservations conflict. Review the material quantity before completing this order.", 409);
            }

            $stmt = $conn->prepare(
                "UPDATE materials
                 SET quantity = quantity - ?
                 WHERE material_id = ? AND quantity >= ?"
            );
            if (!$stmt) {
                throw new RuntimeException("Stock update prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iii", $orderedQuantity, $materialId, $orderedQuantity);
            if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                throw new RuntimeException("Stock update failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    if (!$stmt) {
        throw new RuntimeException("Order status prepare failed: " . $conn->error);
    }
    $stmt->bind_param("si", $newStatus, $orderId);
    if (!$stmt->execute()) {
        throw new RuntimeException("Order status update failed: " . $stmt->error);
    }
    $stmt->close();

    if (!$conn->commit()) {
        throw new RuntimeException("Order transaction commit failed: " . $conn->error);
    }
    updateOrderResponse(true, "Order status updated successfully.");
} catch (Throwable $error) {
    $conn->rollback();
    error_log("Order status update failed: " . $error->getMessage());
    updateOrderResponse(false, "Could not update the order. Please try again.", 500);
}
?>
