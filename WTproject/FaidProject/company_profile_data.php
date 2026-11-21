<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function companyProfileDataError($message, $status)
{
    http_response_code($status);
    echo json_encode([
        "success" => false,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$companyId = filter_var(query_string("id"), FILTER_VALIDATE_INT);
if (!$companyId || $companyId < 1) {
    companyProfileDataError("A valid company ID is required.", 400);
}

$companyStmt = $conn->prepare(
    "SELECT company_id, company_name, description, address, phone, created_at
     FROM companies
     WHERE company_id = ?"
);
if (!$companyStmt) {
    error_log("Company profile query prepare failed: " . $conn->error);
    companyProfileDataError("Could not load this company profile.", 500);
}
$companyStmt->bind_param("i", $companyId);
if (!$companyStmt->execute()) {
    error_log("Company profile query failed: " . $companyStmt->error);
    $companyStmt->close();
    companyProfileDataError("Could not load this company profile.", 500);
}
$company = $companyStmt->get_result()->fetch_assoc();
$companyStmt->close();

if (!$company) {
    $conn->close();
    companyProfileDataError("Company profile not found.", 404);
}

$materialsStmt = $conn->prepare(
    "SELECT m.material_id, m.company_id, m.material_name, m.category,
            m.description, m.quantity,
            GREATEST(
                m.quantity - COALESCE((
                    SELECT SUM(oi.quantity)
                    FROM order_items oi
                    INNER JOIN orders o ON o.order_id = oi.order_id
                    WHERE oi.material_id = m.material_id
                      AND o.status IN ('Pending', 'Approved')
                ), 0),
                0
            ) AS available_quantity,
            m.price, m.image_path, m.created_at
     FROM materials m
     WHERE m.company_id = ?
     ORDER BY m.created_at DESC, m.material_id DESC"
);
if (!$materialsStmt) {
    error_log("Company profile materials prepare failed: " . $conn->error);
    companyProfileDataError("Could not load this company's offers.", 500);
}
$materialsStmt->bind_param("i", $companyId);
if (!$materialsStmt->execute()) {
    error_log("Company profile materials query failed: " . $materialsStmt->error);
    $materialsStmt->close();
    companyProfileDataError("Could not load this company's offers.", 500);
}
$materials = $materialsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$materialsStmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "company" => $company,
    "materials" => $materials
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>
