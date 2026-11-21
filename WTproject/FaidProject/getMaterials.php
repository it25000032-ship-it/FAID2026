<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=UTF-8");

$sql = "
    SELECT
        m.material_id,
        m.company_id,
        c.company_name,
        m.material_name,
        m.category,
        m.description,
        m.quantity,
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
        m.price,
        m.image_path,
        m.created_at
    FROM materials m
    INNER JOIN companies c ON c.company_id = m.company_id
    ORDER BY m.created_at DESC, m.material_id DESC
";

$result = $conn->query($sql);
if (!$result) {
    error_log("Materials query failed: " . $conn->error);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not load offers. Please try again."
    ], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

$materials = [];
while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}

echo json_encode($materials, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$result->free();
$conn->close();
?>
