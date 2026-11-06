
<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin.php#companies");
    exit();
}

$companyId = (int)($_POST["company_id"] ?? 0);

if ($companyId <= 0) {
    die("Invalid company ID.");
}

$conn->begin_transaction();

try {

    // 1. Get all materials belonging to this company
    $stmt = $conn->prepare(
        "SELECT material_id FROM materials WHERE company_id = ?"
    );
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $materialIds = [];

    while ($row = $result->fetch_assoc()) {
        $materialIds[] = (int)$row["material_id"];
    }

    $stmt->close();

    // 2. Delete order items connected to these materials
    foreach ($materialIds as $materialId) {
        $stmt = $conn->prepare(
            "DELETE FROM order_items WHERE material_id = ?"
        );
        $stmt->bind_param("i", $materialId);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Delete orders belonging to this company
    $stmt = $conn->prepare(
        "SELECT order_id FROM orders WHERE buyer_id = ?"
    );
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orderIds = [];

    while ($row = $result->fetch_assoc()) {
        $orderIds[] = (int)$row["order_id"];
    }

    $stmt->close();

    foreach ($orderIds as $orderId) {
        $stmt = $conn->prepare(
            "DELETE FROM order_items WHERE order_id = ?"
        );
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $stmt->close();
    }

    // 4. Delete the company's orders
    $stmt = $conn->prepare(
        "DELETE FROM orders WHERE buyer_id = ?"
    );
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $stmt->close();

    // 5. Delete the company's materials
    $stmt = $conn->prepare(
        "DELETE FROM materials WHERE company_id = ?"
    );
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $stmt->close();

    // 6. Delete the company
    $stmt = $conn->prepare(
        "DELETE FROM companies WHERE company_id = ?"
    );
    $stmt->bind_param("i", $companyId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Company not found.");
    }

    $stmt->close();

    $conn->commit();
    
    header("Location: ../admin.php#companies");
    exit();

} catch (Throwable $e) {

    $conn->rollback();

    die("Delete failed: " . $e->getMessage());
}
?>
