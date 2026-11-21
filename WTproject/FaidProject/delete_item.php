<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/material_images.php";

header("Content-Type: application/json; charset=UTF-8");

function deleteResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    deleteResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$type = post_string("type");
$id = filter_var(post_string("id"), FILTER_VALIDATE_INT);
$allowedTypes = ["companies", "materials", "orders"];
if (!in_array($type, $allowedTypes, true) || !$id || $id < 1) {
    deleteResponse(false, "Invalid record type or ID.", 400);
}
if (!$conn->begin_transaction()) {
    deleteResponse(false, "Could not delete the record. Please try again.", 500);
}

$imagePath = null;
try {
    if ($type === "companies") {
        $stmt = $conn->prepare(
            "SELECT
                (SELECT COUNT(*) FROM materials WHERE company_id = ?) AS material_count,
                (SELECT COUNT(*) FROM orders WHERE buyer_id = ?) AS order_count"
        );
        if (!$stmt) {
            throw new RuntimeException("Company dependency lookup prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ii", $id, $id);
        if (!$stmt->execute()) {
            throw new RuntimeException("Company dependency lookup failed: " . $stmt->error);
        }
        $dependencies = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ((int)$dependencies["material_count"] > 0 || (int)$dependencies["order_count"] > 0) {
            $conn->rollback();
            deleteResponse(false, "This company has offers or orders. Remove or reassign those records first.", 409);
        }

        $stmt = $conn->prepare("DELETE FROM companies WHERE company_id = ?");
        $notFoundMessage = "Company not found.";
        $successMessage = "Company deleted successfully.";
    } elseif ($type === "materials") {
        $stmt = $conn->prepare(
            "SELECT image_path,
                    (SELECT COUNT(*) FROM order_items WHERE material_id = materials.material_id) AS order_count
             FROM materials
             WHERE material_id = ?"
        );
        if (!$stmt) {
            throw new RuntimeException("Material lookup prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new RuntimeException("Material lookup failed: " . $stmt->error);
        }
        $material = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$material) {
            $conn->rollback();
            deleteResponse(false, "Offer not found.", 404);
        }
        if ((int)$material["order_count"] > 0) {
            $conn->rollback();
            deleteResponse(false, "This offer is used in an order and cannot be deleted.", 409);
        }
        $imagePath = $material["image_path"];
        $stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ?");
        $notFoundMessage = "Offer not found.";
        $successMessage = "Offer deleted successfully.";
    } else {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        if (!$stmt) {
            throw new RuntimeException("Order item delete prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new RuntimeException("Order item delete failed: " . $stmt->error);
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $notFoundMessage = "Order not found.";
        $successMessage = "Order and its details deleted successfully.";
    }

    if (!$stmt) {
        throw new RuntimeException("Delete prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new RuntimeException("Delete failed: " . $stmt->error);
    }
    $deleted = $stmt->affected_rows;
    $stmt->close();
    if ($deleted === 0) {
        $conn->rollback();
        deleteResponse(false, $notFoundMessage, 404);
    }
    if (!$conn->commit()) {
        throw new RuntimeException("Delete transaction commit failed: " . $conn->error);
    }

    if ($type === "materials") {
        delete_material_image($imagePath);
    }
    deleteResponse(true, $successMessage);
} catch (Throwable $error) {
    $conn->rollback();
    error_log("Admin delete failed: " . $error->getMessage());
    deleteResponse(false, "Delete failed. Please try again.", 500);
}
?>
