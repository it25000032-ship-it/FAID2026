<?php
require_once __DIR__ . "/security.php";

header("Content-Type: application/json; charset=UTF-8");
echo json_encode(["csrf_token" => csrf_token()], JSON_UNESCAPED_UNICODE);
