<?php
require_once __DIR__ . "/security.php";

header("Content-Type: application/json; charset=UTF-8");
echo json_encode([
    "company_authenticated" => !empty($_SESSION["company_id"]),
    "company_id" => isset($_SESSION["company_id"]) ? (int)$_SESSION["company_id"] : null,
    "company_name" => $_SESSION["companyName"] ?? null
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
