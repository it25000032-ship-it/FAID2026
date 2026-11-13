<?php
session_start();
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";
header("Content-Type: application/json; charset=UTF-8");

$reportType = $_GET["report_type"] ?? "daily"; // daily, weekly, monthly, biannual, yearly, custom
$startDate = $_GET["start_date"] ?? "";
$endDate = $_GET["end_date"] ?? "";
$targetTable = $_GET["target_table"] ?? "materials"; // materials, companies, orders

if ($targetTable === "companies") {
    $sql = "SELECT * FROM companies WHERE 1=1";
    $dateColumn = "created_at";
} elseif ($targetTable === "orders") {
    $sql = "SELECT o.*, c.company_name as buyer_name FROM orders o JOIN companies c ON o.buyer_id = c.company_id WHERE 1=1";
    $dateColumn = "order_date";
} else {

$sql = "SELECT m.*, c.company_name FROM materials m JOIN companies c ON m.company_id = c.company_id WHERE 1=1";
    $dateColumn = "created_at";
}

switch ($reportType) {
    case "daily":
        $sql .= " AND DATE($dateColumn) = CURDATE()";
        break;
    case "weekly":
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case "monthly":
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case "biannual": // نصفي (6 أشهر)
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        break;
    case "yearly": // سنوي
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    case "custom": // من وإلى تاريخ محدد
        if (!empty($startDate) && !empty($endDate)) {
            $sql .= " AND DATE($dateColumn) BETWEEN ? AND ?";
        }
        break;
}
$sql .= " ORDER BY $dateColumn DESC";
$stmt = $conn->prepare($sql);
if ($reportType === "custom" && !empty($startDate) && !empty($endDate)) {
    $stmt->bind_param("ss", $startDate, $endDate);
}
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode([
    "success" => true,
    "report_type" => $reportType,
    "target_table" => $targetTable,
    "total_records" => count($data),
    "data" => $data
]);
?>