<?php
    require_once __DIR__ . "/security.php";
    require_once __DIR__ . "/admin_check.php";
    require_once __DIR__ . "/db.php";
    header("Content-Type: application/json; charset=UTF-8");

    $q = trim(query_string("q"));

    if ($q === "" || strlen($q) > 200) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Enter a keyword of 200 characters or fewer."]);
        exit();
    }

    $like = "%" . $q . "%";

    function runSearch($conn, $sql, $like) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("sss", $like, $like, $like);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    $companies = runSearch($conn,
        "SELECT company_id, company_name, email, phone, address, description
         FROM companies
         WHERE company_name LIKE ? OR email LIKE ? OR phone LIKE ?
         ORDER BY company_id DESC LIMIT 20",
        $like);

    $materials = runSearch($conn,
        "SELECT m.material_id, m.material_name, m.category, m.description,
                m.quantity, m.price, m.company_id, c.company_name
         FROM materials m
         JOIN companies c ON m.company_id = c.company_id
         WHERE m.material_name LIKE ? OR m.category LIKE ? OR c.company_name LIKE ?
         ORDER BY m.material_id DESC LIMIT 20",
        $like);

    $orders = runSearch($conn,
        "SELECT o.order_id, o.order_date, o.status, c.company_name AS buyer_name,
                COALESCE(SUM(oi.quantity), 0) AS item_quantity,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS order_total,
                GROUP_CONCAT(
                    CONCAT(m.material_name, ' x ', oi.quantity)
                    ORDER BY m.material_name SEPARATOR ', '
                ) AS items
         FROM orders o
         JOIN companies c ON o.buyer_id = c.company_id
         LEFT JOIN order_items oi ON oi.order_id = o.order_id
         LEFT JOIN materials m ON m.material_id = oi.material_id
         WHERE CAST(o.order_id AS CHAR) LIKE ? OR c.company_name LIKE ? OR o.status LIKE ?
         GROUP BY o.order_id, o.order_date, o.status, c.company_name
         ORDER BY o.order_id DESC LIMIT 20",
        $like);

    $conn->close();

    if ($companies === null || $materials === null || $orders === null) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Search failed"]);
        exit();
    }

    echo json_encode([
        "success"   => true,
        "companies" => $companies,
        "materials" => $materials,
        "orders"    => $orders
    ], JSON_UNESCAPED_UNICODE);
?>