
<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#materials");
    exit();
}

$name = trim($_POST["material_name"] ?? "");
$category = trim($_POST["category"] ?? "");
$description = trim($_POST["description"] ?? "");
$quantity = (int)($_POST["quantity"] ?? 0);
$price = (float)($_POST["price"] ?? 0);
$companyId = (int)($_POST["company_id"] ?? 0);

if ($name === "" || $category === "" || $quantity <= 0 || $price < 0 || $companyId <= 0) {
    die("Please enter valid material information.");
}

$stmt = $conn->prepare("
    INSERT INTO materials
    (company_id, material_name, category, description, quantity, price)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isssid",
    $companyId,
    $name,
    $category,
    $description,
    $quantity,
    $price
);

if ($stmt->execute()) {
    header("Location: ../admin.php#materials");
    exit();
}

die("Could not add material: " . $stmt->error);
