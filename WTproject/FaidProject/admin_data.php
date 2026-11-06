<?php
require_once __DIR__. "/db.php";
require_once __DIR__ . "/admin_check.php";
$totalCompanies = $conn->query("SELECT COUNT(*) as count FROM companies")->fetch_assoc()['count'];
$totalMaterials = $conn->query("SELECT COUNT(*) as count FROM materials")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];

// ملاحظة: إذا أردت العداد الرابع "Pending Companies" يمكنك تصفيتها بحسب عمود الحالة إذا أضفته لاحقاً، حالياً نجعلها 0 أو نقرؤها
$pendingCompanies = 0; 
$companiesResult = $conn->query("SELECT company_id, company_name, email, created_at FROM companies ORDER BY created_at DESC");

$materialsResult = $conn->query("
    SELECT m.material_id, m.material_name, m.category, m.price, c.company_name 
    FROM materials m 
    JOIN companies c ON m.company_id = c.company_id 
    ORDER BY m.created_at DESC
");
$ordersResult = $conn->query("
    SELECT o.order_id, o.status, o.order_date, c.company_name as buyer_name 
    FROM orders o 
    JOIN companies c ON o.buyer_id = c.company_id 
    ORDER BY o.order_date DESC
");
?>