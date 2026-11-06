<?php
    session_start();
    require_once __DIR__ . "/db.php";
    require_once __DIR__ . "/admin_check.php";
    header("Content-Type: application/json; charset=utf-8");
    $totalCompanies = $conn->query(
     "SELECT COUNT(*) AS count FROM companies"
    )->fetch_assoc()['count'];
    $totalMaterials = $conn->query(
     "SELECT COUNT(*) AS count FROM materials"
    )->fetch_assoc()['count'];
    $totalOrders = $conn->query(
      "SELECT COUNT(*) AS count FROM orders"
    )->fetch_assoc()['count'];
    $pendingCompanies = 0;
  $companiesResult = $conn->query("
    SELECT company_id, company_name, email, phone, address, created_at
    FROM companies
    ORDER BY created_at DESC
");
   $materialsResult = $conn->query("
    SELECT
        m.material_id,
        m.material_name,
        m.category,
        m.description,
        m.quantity,
        m.price,
        m.company_id,
        c.company_name
    FROM materials m
    JOIN companies c ON m.company_id = c.company_id
    ORDER BY m.created_at DESC
");
$ordersResult = $conn->query("
    SELECT
        o.order_id,
        o.status,
        o.order_date,
        c.company_name AS buyer_name
    FROM orders o
    JOIN companies c
    ON o.buyer_id = c.company_id
    ORDER BY o.order_date DESC
");
echo json_encode([
    "totalCompanies" => $totalCompanies,
    "totalMaterials" => $totalMaterials,
    "totalOrders" => $totalOrders,
    "pendingCompanies" => $pendingCompanies,

    "companies" => $companiesResult->fetch_all(MYSQLI_ASSOC),
    "materials" => $materialsResult->fetch_all(MYSQLI_ASSOC),
    "orders" => $ordersResult->fetch_all(MYSQLI_ASSOC)

], JSON_UNESCAPED_UNICODE);

?>