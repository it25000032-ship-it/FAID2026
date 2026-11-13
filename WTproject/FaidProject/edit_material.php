
<?php
session_start();

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#materials");
    exit();
}

$materialId = intval($_POST["material_id"] ?? 0);
$materialName = trim($_POST["material_name"] ?? "");
$category = trim($_POST["category"] ?? "");
$quantity = intval($_POST["quantity"] ?? 0);
$price = floatval($_POST["price"] ?? 0);

if (
    $materialId <= 0 ||
    $materialName === "" ||
    $category === "" ||
    $quantity <= 0 ||
    $price < 0
) {
    die("Invalid material data.");
}

$stmt = $conn->prepare(
    "UPDATE materials
     SET material_name = ?, category = ?, quantity = ?, price = ?
     WHERE material_id = ?"
);

$stmt->bind_param(
    "ssidi",
    $materialName,
    $category,
    $quantity,
    $price,
    $materialId
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: ../admin.php#materials");
    exit();
}

die("Error updating material: " . $stmt->error);
?>
