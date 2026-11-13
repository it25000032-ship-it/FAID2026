<?php
    require_once __DIR__ . "/db.php";
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
    $email       = trim($_POST["email"] ?? "");
    $phone       = trim($_POST["phone"] ?? "");
    $address     = trim($_POST["address"] ?? "");
    $password    = $_POST["password"] ?? "";

    if (
        $companyName === "" ||
        $email === "" ||
        $phone === "" ||
        $address === "" ||
        $password === ""
    ) {
        sendResponse(false, "Please fill in all fields.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "Invalid email address.");
    }

    if (strlen($password) < 8) {
        sendResponse(false, "Password must be at least 8 characters.");
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

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO companies
            (company_name, email, phone, address, password_hash)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        sendResponse(false, "Failed to prepare registration.");
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
        $stmt->close();
        $conn->close();
        sendResponse(true, "Registration successful!");
    }

    $errno = $stmt->errno;
    $stmt->close();
    $conn->close();

    if ($errno == 1062) {
        sendResponse(false, "This email is already registered. Please use another email.");
    }
    sendResponse(false, "Registration failed. Please try again.");
?>