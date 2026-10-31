<?php

require_once DIR. "/db.php";
  header("Content-Type: application/json; charset=UTF-8");
mysqli_report(MYSQLI_REPORT_OFF);

function sendResponse($success, $message) {
    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
   sendResponse(false, "Invalid request.");
}

$companyName = trim($_POST["companyName"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["address"] ?? "");
$password = $_POST["password"] ?? "";

if (
    $companyName === "" 
    $email === "" 
    $phone === "" 
    $address === "" 
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
$check = $conn->prepare(
    "SELECT company_id FROM companies WHERE email = ?"
);

$check->bind_param("s", $email);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    $check->close();
    $conn->close();
    sendResponse(false, "This email is already registered. Please use another email.");
}

$check->close();
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


mysqli_report(MYSQLI_REPORT_OFF);

if ($stmt->execute()) {

    echo "Registration successful!";

} else {

    if ($stmt->errno == 1062) {
        echo "This email is already registered. Please use another email.";
    } else {
        echo "Registration failed. Please try again.";
    }

}

$stmt->close();
$conn->close();

?>