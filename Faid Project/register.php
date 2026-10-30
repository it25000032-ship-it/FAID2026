<?php

require_once __DIR__. "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request.");
}

$companyName = trim($_POST["companyName"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["address"] ?? "");
$password = $_POST["password"] ?? "";

if (
    $companyName === "" ||
    $email === "" ||
    $phone === "" ||
    $address === "" ||
    $password === ""
) {
    exit("Please fill in all fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Invalid email address.");
}

if (strlen($password) < 8) {
    exit("Password must be at least 8 characters.");
}

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$sql = "INSERT INTO companies
        (company_name, email, phone, address, password_hash)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit("Failed to prepare registration.");
}

$stmt->bind_param(
    "sssss",
    $companyName,
    $email,
    $phone,
    $address,
    $passwordHash
);

if ($stmt->execute()) {
    echo "Registration successful!";
} else {
    if ($stmt->errno === 1062) {
        echo "This email is already registered.";
    } else {
        echo "Registration failed.";
    }
}

$stmt->close();
$conn->close();

?>