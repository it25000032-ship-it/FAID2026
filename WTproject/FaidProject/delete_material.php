<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/material_images.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#materials");
    exit();
}
require_csrf_token();

$id = filter_var(post_string("material_id"), FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid offer ID.");
}

if (!$conn->begin_transaction()) {
    http_response_code(500);
    exit("Could not delete the offer. Please try again.");
}

$stmt = $conn->prepare(
    "SELECT image_path,
            (SELECT COUNT(*) FROM order_items WHERE material_id = materials.material_id) AS order_count
     FROM materials
     WHERE material_id = ?"
);
if (!$stmt) {
    $conn->rollback();
    error_log("Material deletion lookup prepare failed: " . $conn->error);
    http_response_code(500);
    exit("Could not delete the offer. Please try again.");
}
$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log("Material deletion lookup failed: " . $stmt->error);
    http_response_code(500);
    exit("Could not delete the offer. Please try again.");
}
$material = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$material) {
    $conn->rollback();
    http_response_code(404);
    exit("Offer not found.");
}
if ((int)$material["order_count"] > 0) {
    $conn->rollback();
    http_response_code(409);
    exit("This offer is used in an order and cannot be deleted.");
}

$stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log("Material delete prepare failed: " . $conn->error);
    http_response_code(500);
    exit("Could not delete the offer. Please try again.");
}
$stmt->bind_param("i", $id);
if (!$stmt->execute() || $stmt->affected_rows !== 1) {
    $conn->rollback();
    error_log("Material delete failed: " . $stmt->error);
    $stmt->close();
    http_response_code(500);
    exit("Could not delete the offer. Please try again.");
}
$stmt->close();
if (!$conn->commit()) {
    $conn->rollback();
    http_response_code(500);
    exit("Could not delete the offer. Please try again.");
}
$conn->close();
delete_material_image($material["image_path"]);
header("Location: ../admin.php#materials");
exit();
?>
