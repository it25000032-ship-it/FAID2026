<?php
    header("Content-Type: application/json; charset=UTF-8");
    require_once __DIR__ . "/db.php";
    require_once __DIR__ . "/admin_check.php";

    $reportType  = $_GET["report_type"] ?? "daily";
    $targetTable = $_GET["target_table"] ?? "materials";
    $startDate   = $_GET["start_date"] ?? "";
    $endDate     = $_GET["end_date"] ?? "";

    $allowedTypes = ["daily", "weekly", "monthly", "biannual", "yearly", "custom"];

    if (!in_array($reportType, $allowedTypes, true)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid report type"
        ]);
        exit();
    }

    $allowedTables = ["materials", "companies", "orders"];

    if (!in_array($targetTable, $allowedTables, true)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid report category"
        ]);
        exit();
    }

    if ($reportType === "custom") {
        $validStart = DateTime::createFromFormat("!Y-m-d", $startDate);
        $validEnd   = DateTime::createFromFormat("!Y-m-d", $endDate);

        if (
            !$validStart ||
            !$validEnd ||
            $validStart->format("Y-m-d") !== $startDate ||
            $validEnd->format("Y-m-d") !== $endDate ||
            $startDate > $endDate
        ) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Please select valid start and end dates"
            ]);
            exit();
        }
    }

    if ($targetTable === "companies") {
        $sql = "
            SELECT
                company_id,
                company_name,
                email,
                phone,
                address,
                created_at
            FROM companies
            WHERE 1=1
        ";
        $dateColumn = "created_at";
    } elseif ($targetTable === "materials") {
        $sql = "
            SELECT
                m.material_id,
                m.company_id,
                m.material_name,
                m.category,
                m.description,
                m.quantity,
                m.price,
                m.created_at,
                c.company_name
            FROM materials m
            JOIN companies c
                ON m.company_id = c.company_id
            WHERE 1=1
        ";
        $dateColumn = "m.created_at";
    } else {
        $sql = "
            SELECT
                o.order_id,
                o.buyer_id,
                o.order_date,
                o.status,
                c.company_name AS buyer_name,
                COALESCE(SUM(oi.quantity), 0) AS item_quantity,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS order_total
            FROM orders o
            JOIN companies c
                ON o.buyer_id = c.company_id
            LEFT JOIN order_items oi
                ON o.order_id = oi.order_id
            WHERE 1=1
        ";
        $dateColumn = "o.order_date";
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
        case "biannual":
            $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
            break;
        case "yearly":
            $sql .= " AND $dateColumn >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        case "custom":
            $sql .= " AND DATE($dateColumn) BETWEEN ? AND ?";
            break;
    }

    if ($targetTable === "orders") {
        $sql .= "
            GROUP BY
                o.order_id,
                o.buyer_id,
                o.order_date,
                o.status,
                c.company_name
        ";
    }

    $sql .= " ORDER BY $dateColumn DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to prepare report query"
        ]);
        exit();
    }

    if ($reportType === "custom") {
        $stmt->bind_param("ss", $startDate, $endDate);
    }

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to generate report"
        ]);
        $stmt->close();
        $conn->close();
        exit();
    }

    $result = $stmt->get_result();
    $data = [];

    $totalListings  = 0;
    $totalQuantity  = 0;
    $totalPurchases = 0;
    $totalSales     = 0;
    $totalCompanies = 0;
    $totalMaterials = 0;
    $totalOrders    = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    if ($targetTable === "materials") {
        $totalMaterials = count($data);
        foreach ($data as $material) {
            $totalQuantity += (int)$material["quantity"];
        }
        $totalListings = $totalMaterials;
    } elseif ($targetTable === "companies") {
        $totalCompanies = count($data);
    } elseif ($targetTable === "orders") {
        $totalOrders = count($data);
        foreach ($data as $order) {
            $totalQuantity += (int)$order["item_quantity"];
            $totalSales    += (float)$order["order_total"];
            if ($order["status"] === "Completed") {
                $totalPurchases++;
            }
        }
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success"      => true,
        "report_type"  => $reportType,
        "target_table" => $targetTable,
        "summary" => [
            "total_records"   => count($data),
            "total_listings"  => $totalListings,
            "total_quantity"  => $totalQuantity,
            "total_savings"   => null,
            "total_purchases" => $totalPurchases,
            "total_sales"     => $totalSales,
            "total_companies" => $totalCompanies,
            "total_materials" => $totalMaterials,
            "total_orders"    => $totalOrders
        ],
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);
?>