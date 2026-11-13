
<?php
session_start();

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#companies");
    exit();
}

$companyName = trim($_POST["company_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["address"] ?? "");
$password = $_POST["password"] ?? "";

if ($companyName === "" || $email === "" || $password === "") {
    die("Please fill in all required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO companies
    (company_name, email, phone, address, password_hash)
    VALUES (?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "sssss",
    $companyName,
    $email,
    $phone,
    $address,
    $passwordHash
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: ../admin.php#companies");
    exit();
} else {
    if ($conn->errno === 1062) {
        die("This email is already registered.");
    }

    die("Error adding company: " . $stmt->error);
}
?>
