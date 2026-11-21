<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");
    ini_set("session.cookie_httponly", "1");

    $isHttps = isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "" &&
        strtolower((string)$_SERVER["HTTPS"]) !== "off";

    session_name("FAIDSESSID");
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    if (!session_start()) {
        throw new RuntimeException("Could not start the session.");
    }

    $now = time();
    if (isset($_SESSION["last_activity"]) && $now - (int)$_SESSION["last_activity"] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION["last_activity"] = $now;
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrf_rotate_token(): string
{
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    return $_SESSION["csrf_token"];
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? "";
    return is_string($value) ? $value : "";
}

function query_string(string $key): string
{
    $value = $_GET[$key] ?? "";
    return is_string($value) ? $value : "";
}

function csrf_is_valid(): bool
{
    $provided = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? $_POST["_csrf_token"] ?? "";
    $stored = $_SESSION["csrf_token"] ?? "";

    return is_string($provided) &&
        is_string($stored) &&
        $provided !== "" &&
        $stored !== "" &&
        hash_equals($stored, $provided);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") .
        '">';
}

function require_csrf_token(bool $json = false): void
{
    if (csrf_is_valid()) {
        return;
    }

    http_response_code(403);
    if ($json) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => false,
            "message" => "Your session token expired. Refresh the page and try again."
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo "Your session token expired. Refresh the page and try again.";
    }
    exit;
}
