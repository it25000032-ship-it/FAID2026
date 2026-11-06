
<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#companies");
    exit();
}

$id = (int)($_POST["company_id"] ?? 0);
$name = trim($_POST["company_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["address"] ?? "");

if ($id <= 0 || $name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid company information.");
}

$stmt = $conn->prepare(
    "UPDATE companies
     SET company_name = ?, email = ?, phone = ?, address = ?
     WHERE company_id = ?"
);

$stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);

if ($stmt->execute()) {
    header("Location: ../admin.php#companies");
    exit();
}

die("Update failed: " . $stmt->error);
