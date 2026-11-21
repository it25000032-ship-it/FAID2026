<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function orderResponse($success, $message, $status = 200, $extra = [])
{
    http_response_code($status);
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    orderResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$buyerId = (int)($_SESSION["company_id"] ?? 0);
$materialId = filter_var(post_string("material_id"), FILTER_VALIDATE_INT);
$quantityInput = post_string("quantity");
if ($buyerId < 1) {
    orderResponse(false, "Please sign in with a company account to request an offer.", 401);
}
if (!$materialId || $materialId < 1 || filter_var($quantityInput, FILTER_VALIDATE_INT) === false) {
    orderResponse(false, "Enter a valid offer and quantity.", 400);
}
$quantity = (int)$quantityInput;
if ($quantity < 1 || $quantity > 2147483647) {
    orderResponse(false, "Quantity must be a positive whole number.", 400);
}

if (!$conn->begin_transaction()) {
    error_log("Could not begin material request transaction: " . $conn->error);
    orderResponse(false, "Could not place the request. Please try again.", 500);
}

try {
    $stmt = $conn->prepare(
        "SELECT company_id, quantity, price
         FROM materials
         WHERE material_id = ?
         FOR UPDATE"
    );
    if (!$stmt) {
        throw new RuntimeException("Material lookup prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $materialId);
    if (!$stmt->execute()) {
        throw new RuntimeException("Material lookup failed: " . $stmt->error);
    }
    $material = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$material) {
        $conn->rollback();
        orderResponse(false, "This offer is no longer available.", 404);
    }
    if ((int)$material["company_id"] === $buyerId) {
        $conn->rollback();
        orderResponse(false, "A company cannot request its own offer.", 400);
    }

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(oi.quantity), 0) AS reserved
         FROM order_items oi
         INNER JOIN orders o ON o.order_id = oi.order_id
         WHERE oi.material_id = ?
           AND o.status IN ('Pending', 'Approved')"
    );
    if (!$stmt) {
        throw new RuntimeException("Reservation lookup prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $materialId);
    if (!$stmt->execute()) {
        throw new RuntimeException("Reservation lookup failed: " . $stmt->error);
    }
    $reserved = (int)$stmt->get_result()->fetch_assoc()["reserved"];
    $stmt->close();

    $available = max(0, (int)$material["quantity"] - $reserved);
    if ($quantity > $available) {
        $conn->rollback();
        orderResponse(false, "Only " . $available . " unit(s) are currently available.", 409, [
            "available_quantity" => $available
        ]);
    }

    $status = "Pending";
    $stmt = $conn->prepare("INSERT INTO orders (buyer_id, status) VALUES (?, ?)");
    if (!$stmt) {
        throw new RuntimeException("Order insert prepare failed: " . $conn->error);
    }
    $stmt->bind_param("is", $buyerId, $status);
    if (!$stmt->execute()) {
        throw new RuntimeException("Order insert failed: " . $stmt->error);
    }
    $orderId = $stmt->insert_id;
    $stmt->close();

    $unitPrice = (float)$material["price"];
    $stmt = $conn->prepare(
        "INSERT INTO order_items (order_id, material_id, quantity, unit_price)
         VALUES (?, ?, ?, ?)"
    );
    if (!$stmt) {
        throw new RuntimeException("Order item prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iiid", $orderId, $materialId, $quantity, $unitPrice);
    if (!$stmt->execute()) {
        throw new RuntimeException("Order item insert failed: " . $stmt->error);
    }
    $stmt->close();

    if (!$conn->commit()) {
        throw new RuntimeException("Order transaction commit failed: " . $conn->error);
    }

    orderResponse(true, "Offer request submitted. The quantity is reserved while it is pending.", 201, [
        "order_id" => $orderId,
        "available_quantity" => $available - $quantity
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log("Material request failed: " . $error->getMessage());
    orderResponse(false, "Could not place the request. Please try again.", 500);
}
?>
