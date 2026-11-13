
<?php
   session_start();
   require_once __DIR__ . "/db.php";
   header("Content-Type: application/json; charset=UTF-8");
   function sendResponse($success, $message) {
         echo json_encode([
         "success" => $success,
         "message" => $message
         ]);
   exit;
   }
   if ($_SERVER["REQUEST_METHOD"] !== "POST") {
         sendResponse(false, "Invalid request method.");
   }
   if (!isset($_SESSION["company_id"])) {
         sendResponse(false, "Please login first.");
   }
   $companyId = $_SESSION["company_id"];
   $materialName = trim($_POST["material_name"] ?? "");
   $category = trim($_POST["category"] ?? "");
   $description = trim($_POST["description"] ?? "");
   $quantity = $_POST["quantity"] ?? "";
   $price = $_POST["price"] ?? "";
   if ($materialName === "" ||$category === "" ||$description === "" ||$quantity === "" ||$price === "") {
     sendResponse(false, "Please fill in all fields.");
   }
   if (!is_numeric($quantity) ||!is_numeric($price) || $quantity <= 0 ||$price < 0) {
    sendResponse(false, "Invalid quantity or price.");
   }
  $quantity = (int)$quantity;
  $price = (float)$price;
  $imagePath = null;
  if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        sendResponse(false, "Image upload failed.");
    }
    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif",
        "image/webp" => "webp"
    ];
    $fileType = mime_content_type($_FILES["image"]["tmp_name"]);
    if (!isset($allowedTypes[$fileType])) {
        sendResponse(false, "Only JPG, PNG, GIF and WEBP images are allowed.");
    }
    if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
        sendResponse(false, "Image size exceeds 5MB.");
    }
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        sendResponse(false, "Failed to create uploads folder.");
    }
    $fileName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$fileType];
    $destination = $uploadDir . $fileName;
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $destination)) {
        sendResponse(false, "Failed to save image.");
    }
    $imagePath = "uploads/" . $fileName;
}


$sql = "INSERT INTO materials(company_id, material_name, category, description, quantity, price, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    if ($imagePath !== null) {
        unlink(__DIR__ . "/" . $imagePath);
    }
    sendResponse(false, "Database error: " . $conn->error);
}
$stmt->bind_param(
    "isssids",
    $companyId,
    $materialName,
    $category,
    $description,
    $quantity,
    $price,
    $imagePath
);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    sendResponse(true, "Material added successfully.");
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    if ($imagePath !== null && file_exists(__DIR__ . "/" . $imagePath)) {
        unlink(__DIR__ . "/" . $imagePath);
    }
    sendResponse(false, "Failed to add material: " . $error);
}
?>
