<?php
$host = getenv("FAID_DB_HOST") ?: "localhost";
$username = getenv("FAID_DB_USER") ?: "root";
$password = getenv("FAID_DB_PASSWORD");
$database = getenv("FAID_DB_NAME") ?: "faidproject";

if ($password === false) {
    $password = "";
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    error_log("FAID database connection failed: " . $conn->connect_error);
    http_response_code(500);
    exit("Database service is unavailable.");
}

if (!$conn->set_charset("utf8mb4")) {
    error_log("FAID database charset setup failed: " . $conn->error);
    $conn->close();
    http_response_code(500);
    exit("Database service is unavailable.");
}
?>