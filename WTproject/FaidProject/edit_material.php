<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/material_images.php";

header("Content-Type: application/json; charset=UTF-8");

function editMaterialResponse($success, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    editMaterialResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

$materialId = filter_var(post_string("material_id"), FILTER_VALIDATE_INT);
$materialName = trim(post_string("material_name"));
$category = trim(post_string("category"));
$description = trim(post_string("description"));
$quantityInput = post_string("quantity");
$priceInput = post_string("price");
$allowedCategories = ["wood", "metal", "packaging", "plastic", "electrical"];

if (
    !$materialId ||
    $materialId < 1 ||
    $materialName === "" ||
    strlen($materialName) > 150 ||
    !in_array($category, $allowedCategories, true) ||
    filter_var($quantityInput, FILTER_VALIDATE_INT) === false ||
    (int)$quantityInput < 1 ||
    (int)$quantityInput > 2147483647 ||
    !is_numeric($priceInput) ||
    (float)$priceInput < 0 ||
    (float)$priceInput > 99999999.99
) {
    editMaterialResponse(false, "Please enter valid offer information.", 400);
}

$quantity = (int)$quantityInput;
$price = (float)$priceInput;
$newImagePath = null;
try {
    $newImagePath = save_material_image($_FILES["image"] ?? []);
} catch (RuntimeException $error) {
    editMaterialResponse(false, $error->getMessage(), 400);
}

$removeImage = post_string("remove_image") === "1";
if (!$conn->begin_transaction()) {
    delete_material_image($newImagePath);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}
$stmt = $conn->prepare("SELECT image_path FROM materials WHERE material_id = ? FOR UPDATE");
if (!$stmt) {
    $conn->rollback();
    delete_material_image($newImagePath);
    error_log("Material lookup prepare failed: " . $conn->error);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}
$stmt->bind_param("i", $materialId);
if (!$stmt->execute()) {
    $conn->rollback();
    delete_material_image($newImagePath);
    error_log("Material lookup failed: " . $stmt->error);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$existing) {
    $conn->rollback();
    delete_material_image($newImagePath);
    editMaterialResponse(false, "Offer not found.", 404);
}

$oldImagePath = $existing["image_path"];
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(oi.quantity), 0) AS reserved
     FROM order_items oi
     INNER JOIN orders o ON o.order_id = oi.order_id
     WHERE oi.material_id = ?
       AND o.status IN ('Pending', 'Approved')"
);
if (!$stmt) {
    $conn->rollback();
    delete_material_image($newImagePath);
    error_log("Material reservation lookup prepare failed: " . $conn->error);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}
$stmt->bind_param("i", $materialId);
if (!$stmt->execute()) {
    $conn->rollback();
    delete_material_image($newImagePath);
    error_log("Material reservation lookup failed: " . $stmt->error);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}
$reserved = (int)$stmt->get_result()->fetch_assoc()["reserved"];
$stmt->close();
if ($quantity < $reserved) {
    $conn->rollback();
    delete_material_image($newImagePath);
    editMaterialResponse(
        false,
        "Quantity cannot be lower than the amount reserved by pending or approved orders.",
        409
    );
}

$imagePath = $newImagePath !== null ? $newImagePath : ($removeImage ? null : $oldImagePath);
$stmt = $conn->prepare(
    "UPDATE materials
     SET material_name = ?, category = ?, description = ?, quantity = ?, price = ?, image_path = ?
     WHERE material_id = ?"
);
if (!$stmt) {
    $conn->rollback();
    delete_material_image($newImagePath);
    error_log("Material update prepare failed: " . $conn->error);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}
$stmt->bind_param(
    "sssidsi",
    $materialName,
    $category,
    $description,
    $quantity,
    $price,
    $imagePath,
    $materialId
);

if (!$stmt->execute()) {
    $conn->rollback();
    error_log("Material update failed: " . $stmt->error);
    $stmt->close();
    delete_material_image($newImagePath);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}

$stmt->close();
if (!$conn->commit()) {
    $conn->rollback();
    delete_material_image($newImagePath);
    error_log("Material update commit failed: " . $conn->error);
    editMaterialResponse(false, "Could not update the offer. Please try again.", 500);
}
$conn->close();
if ($oldImagePath !== $imagePath) {
    delete_material_image($oldImagePath);
}
editMaterialResponse(true, "Offer updated successfully.");
?>
