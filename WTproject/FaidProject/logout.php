<?php
require_once __DIR__ . "/security.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Invalid request method.");
}
require_csrf_token();

$wasAdmin = isset($_SESSION["admin_id"]);
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), "", [
    "expires" => time() - 42000,
    "path" => $params["path"],
    "domain" => $params["domain"],
    "secure" => $params["secure"],
    "httponly" => $params["httponly"],
    "samesite" => $params["samesite"] ?? "Lax"
]);
session_destroy();

if (isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["success" => true, "message" => "Signed out."]);
    exit();
}

header("Location: " . ($wasAdmin ? "admin_login.php" : "../home.html"));
exit();
?>