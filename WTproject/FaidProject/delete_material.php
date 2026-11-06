
<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

$id = (int)($_POST["material_id"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || $id <= 0) {
    header("Location: ../admin.php#materials");
    exit();
}

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM order_items WHERE material_id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$used = $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

if ($used > 0) {
    die("Cannot delete this material because it is used in existing orders.");
}

$stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: ../admin.php#materials");
    exit();
}

die("Material not found or could not be deleted.");
