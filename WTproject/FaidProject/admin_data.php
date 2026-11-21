<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function adminDataError($message = "Could not load dashboard data.")
{
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$counts = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM companies) AS total_companies,
        (SELECT COUNT(*) FROM materials) AS total_materials,
        (SELECT COUNT(*) FROM orders) AS total_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'Pending') AS pending_orders
");
if (!$counts) {
    error_log("Dashboard counts query failed: " . $conn->error);
    adminDataError();
}
$stats = $counts->fetch_assoc();
$counts->free();

$companiesResult = $conn->query("
    SELECT company_id, company_name, email, phone, address, description, created_at
    FROM companies
    ORDER BY created_at DESC, company_id DESC
");
if (!$companiesResult) {
    error_log("Dashboard companies query failed: " . $conn->error);
    adminDataError();
}
$companies = $companiesResult->fetch_all(MYSQLI_ASSOC);
$companiesResult->free();

$materialsResult = $conn->query("
    SELECT m.material_id, m.material_name, m.category, m.description,
           m.quantity,
           GREATEST(m.quantity - COALESCE(reserved.reserved_quantity, 0), 0) AS available_quantity,
           m.price, m.image_path, m.company_id, m.created_at, c.company_name
    FROM materials m
    INNER JOIN companies c ON c.company_id = m.company_id
    LEFT JOIN (
        SELECT oi.material_id, SUM(oi.quantity) AS reserved_quantity
        FROM order_items oi
        INNER JOIN orders o ON o.order_id = oi.order_id
        WHERE o.status IN ('Pending', 'Approved')
        GROUP BY oi.material_id
    ) reserved ON reserved.material_id = m.material_id
    ORDER BY m.created_at DESC, m.material_id DESC
");
if (!$materialsResult) {
    error_log("Dashboard materials query failed: " . $conn->error);
    adminDataError();
}
$materials = $materialsResult->fetch_all(MYSQLI_ASSOC);
$materialsResult->free();

$ordersResult = $conn->query("
    SELECT o.order_id, o.status, o.order_date, c.company_name AS buyer_name,
           COALESCE(SUM(oi.quantity), 0) AS item_quantity,
           COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS order_total,
           GROUP_CONCAT(
               CONCAT(m.material_name, ' x ', oi.quantity)
               ORDER BY m.material_name SEPARATOR ', '
           ) AS items
    FROM orders o
    INNER JOIN companies c ON c.company_id = o.buyer_id
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    LEFT JOIN materials m ON m.material_id = oi.material_id
    GROUP BY o.order_id, o.status, o.order_date, c.company_name
    ORDER BY o.order_date DESC, o.order_id DESC
");
if (!$ordersResult) {
    error_log("Dashboard orders query failed: " . $conn->error);
    adminDataError();
}
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);
$ordersResult->free();
$conn->close();

echo json_encode([
    "success" => true,
    "totalCompanies" => (int)$stats["total_companies"],
    "totalMaterials" => (int)$stats["total_materials"],
    "totalOrders" => (int)$stats["total_orders"],
    "pendingOrders" => (int)$stats["pending_orders"],
    "companies" => $companies,
    "materials" => $materials,
    "orders" => $orders
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>
