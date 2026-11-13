<?php
    header("Content-Type: application/json; charset=UTF-8");
    require_once __DIR__ . "/db.php";
    require_once __DIR__ . "/admin_check.php";

    $q = trim($_GET["q"] ?? "");

    if ($q === "") {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Please type a keyword"]);
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
        "SELECT company_id, company_name, email, phone, address
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
        "SELECT o.order_id, o.order_date, o.status, c.company_name AS buyer_name
         FROM orders o
         JOIN companies c ON o.buyer_id = c.company_id
         WHERE CAST(o.order_id AS CHAR) LIKE ? OR c.company_name LIKE ? OR o.status LIKE ?
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