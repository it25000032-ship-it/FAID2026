<?php
session_start();
require_once __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit("Invalid request.");
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    exit("Please fill in all fields.");
}

$sql = "SELECT id, companyName, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
   
    if (password_verify($password, $row["password"])) {
      
        $_SESSION["user_id"] = $row["id"];
        $_SESSION["companyName"] = $row["companyName"];
        
        echo "Login successful! Welcome " . htmlspecialchars($row["companyName"]);
   
    } else {
        echo "Invalid email or password.";
    }
} else {
    echo "Invalid email or password.";
}

$stmt->close();
$conn->close();
?>