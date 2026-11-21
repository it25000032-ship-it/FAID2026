<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/admin_check.php";
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

function reportError($message, $status)
{
    http_response_code($status);
    echo json_encode([
        "success" => false,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$reportType = query_string("report_type") ?: "daily";
$targetTable = query_string("target_table") ?: "materials";
$startDate = query_string("start_date");
$endDate = query_string("end_date");
$allowedTypes = ["daily", "weekly", "monthly", "biannual", "yearly", "custom"];
$allowedTables = ["materials", "companies", "orders"];

if (!in_array($reportType, $allowedTypes, true)) {
    reportError("Invalid report period.", 400);
}
if (!in_array($targetTable, $allowedTables, true)) {
    reportError("Invalid report category.", 400);
}

if ($reportType === "custom") {
    $validStart = DateTime::createFromFormat("!Y-m-d", $startDate);
    $validEnd = DateTime::createFromFormat("!Y-m-d", $endDate);
    if (
        !$validStart ||
        !$validEnd ||
        $validStart->format("Y-m-d") !== $startDate ||
        $validEnd->format("Y-m-d") !== $endDate ||
        $startDate > $endDate
    ) {
        reportError("Select valid dates with the start date on or before the end date.", 400);
    }
}

if ($targetTable === "companies") {
    $sql = "
        SELECT company_id, company_name, email, phone, address, created_at
        FROM companies
        WHERE 1=1
    ";
    $dateColumn = "created_at";
} elseif ($targetTable === "materials") {
    $sql = "
        SELECT m.material_id, m.company_id, m.material_name, m.category,
               m.description, m.quantity, m.price, m.created_at, c.company_name
        FROM materials m
        INNER JOIN companies c ON c.company_id = m.company_id
        WHERE 1=1
    ";
    $dateColumn = "m.created_at";
} else {
    $sql = "
        SELECT o.order_id, o.buyer_id, o.order_date, o.status,
               c.company_name AS buyer_name,
               COALESCE(SUM(oi.quantity), 0) AS item_quantity,
               COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS order_total
        FROM orders o
        INNER JOIN companies c ON c.company_id = o.buyer_id
        LEFT JOIN order_items oi ON oi.order_id = o.order_id
        WHERE 1=1
    ";
    $dateColumn = "o.order_date";
}

$bindDates = false;
switch ($reportType) {
    case "daily":
        $sql .= " AND $dateColumn >= CURDATE()
                  AND $dateColumn < DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
        break;
    case "weekly":
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND $dateColumn < NOW()";
        break;
    case "monthly":
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                  AND $dateColumn < NOW()";
        break;
    case "biannual":
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  AND $dateColumn < NOW()";
        break;
    case "yearly":
        $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                  AND $dateColumn < NOW()";
        break;
    case "custom":
        $sql .= " AND $dateColumn >= ? AND $dateColumn < DATE_ADD(?, INTERVAL 1 DAY)";
        $bindDates = true;
        break;
}

if ($targetTable === "orders") {
    $sql .= "
        GROUP BY o.order_id, o.buyer_id, o.order_date, o.status, c.company_name
    ";
}
$sql .= " ORDER BY $dateColumn DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Report query prepare failed: " . $conn->error);
    reportError("Could not prepare the report. Please try again.", 500);
}
if ($bindDates) {
    $startDateTime = $startDate . " 00:00:00";
    $endDateTime = $endDate . " 00:00:00";
    $stmt->bind_param("ss", $startDateTime, $endDateTime);
}
if (!$stmt->execute()) {
    error_log("Report query failed: " . $stmt->error);
    $stmt->close();
    reportError("Could not generate the report. Please try again.", 500);
}

$result = $stmt->get_result();
$data = [];
$totalQuantity = 0;
$totalCompletedPurchases = 0;
$completedSales = 0.0;
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    if ($targetTable === "materials") {
        $totalQuantity += (int)$row["quantity"];
    } elseif ($targetTable === "orders") {
        $totalQuantity += (int)$row["item_quantity"];
        if ($row["status"] === "Completed") {
            $totalCompletedPurchases++;
            $completedSales += (float)$row["order_total"];
        }
    }
}

$summary = [
    "total_records" => count($data),
    "total_listings" => $targetTable === "materials" ? count($data) : 0,
    "total_quantity" => $totalQuantity,
    "total_savings" => null,
    "total_purchases" => $totalCompletedPurchases,
    "total_sales" => $completedSales
];
$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "report_type" => $reportType,
    "target_table" => $targetTable,
    "summary" => $summary,
    "data" => $data
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>
