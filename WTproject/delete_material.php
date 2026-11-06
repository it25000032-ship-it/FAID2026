<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_check.php";

if (isset($_GET["id"])) {
    $materialId = (int)$_GET["id"];
    $stmtImg = $conn->prepare("SELECT image_path FROM materials WHERE material_id = ?");
    $stmtImg->bind_param("i", $materialId);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    if ($rowImg = $resImg->fetch_assoc()) {
        if (!empty($rowImg['image_path']) && file_exists(DIR . "/" . $rowImg['image_path'])) {
            unlink(DIR . "/" . $rowImg['image_path']);
        }
    }
    $stmtImg->close();
    $stmt = $conn->prepare("DELETE FROM materials WHERE material_id = ?");
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
header("Location: admin.php");
exit();
?>