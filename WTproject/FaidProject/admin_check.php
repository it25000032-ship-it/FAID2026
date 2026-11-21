<?php
require_once __DIR__ . "/security.php";

if (!isset($_SESSION["admin_id"])) {
    if (basename($_SERVER["SCRIPT_NAME"] ?? "") === "admin.php") {
        header("Location: FaidProject/admin_login.php");
        exit();
    }

    http_response_code(401);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false,
        "message" => "Please sign in as an administrator."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>