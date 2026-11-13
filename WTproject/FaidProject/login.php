<?php
    session_start();
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

    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        sendResponse(false, "Please fill in all fields.");
    }

    $sql = "SELECT company_id, company_name, password_hash
            FROM companies
            WHERE email = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        sendResponse(false, "Login failed. Please try again.");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    if ($row && password_verify($password, $row["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"]     = $row["company_id"];
        $_SESSION["companyName"] = $row["company_name"];
        sendResponse(true, "Login successful! Welcome " . $row["company_name"]);
    }

    sendResponse(false, "Invalid email or password.");
?>