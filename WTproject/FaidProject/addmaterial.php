<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/material_images.php";

header("Content-Type: application/json; charset=UTF-8");

function sendMaterialResponse($success, $message, $status = 200, $extra = [])
{
    http_response_code($status);
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendMaterialResponse(false, "Invalid request method.", 405);
}
require_csrf_token(true);

if (isset($_SESSION["admin_id"])) {
    $companyId = filter_var(post_string("company_id"), FILTER_VALIDATE_INT);
    if (!$companyId || $companyId < 1) {
        sendMaterialResponse(false, "Select a valid company.", 400);
    }
} elseif (!empty($_SESSION["company_id"])) {
    $companyId = (int)$_SESSION["company_id"];
} else {
    sendMaterialResponse(false, "Please sign in with a company account first.", 401);
}
require_once __DIR__ . "/db.php";

$materialName = trim(post_string("material_name"));
$category = trim(post_string("category"));
$description = trim(post_string("description"));
$quantityInput = post_string("quantity");
$priceInput = post_string("price");
$allowedCategories = ["wood", "metal", "packaging", "plastic", "electrical"];

if ($materialName === "" || $quantityInput === "" || $priceInput === "") {
    sendMaterialResponse(false, "Please enter a name, quantity, and price.", 400);
}
if (strlen($materialName) > 150 || !in_array($category, $allowedCategories, true)) {
    sendMaterialResponse(false, "Please enter a valid offer name and category.", 400);
}
if (
    filter_var($quantityInput, FILTER_VALIDATE_INT) === false ||
    (int)$quantityInput < 1 ||
    (int)$quantityInput > 2147483647 ||
    !is_numeric($priceInput) ||
    (float)$priceInput < 0 ||
    (float)$priceInput > 99999999.99
) {
    sendMaterialResponse(false, "Please enter a valid quantity and price.", 400);
}

$quantity = (int)$quantityInput;
$price = (float)$priceInput;
$imagePath = null;

try {
    $imagePath = save_material_image($_FILES["image"] ?? []);
} catch (RuntimeException $error) {
    sendMaterialResponse(false, $error->getMessage(), 400);
}

$stmt = $conn->prepare(
    "INSERT INTO materials
        (company_id, material_name, category, description, quantity, price, image_path)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    delete_material_image($imagePath);
    error_log("Material insert prepare failed: " . $conn->error);
    sendMaterialResponse(false, "Could not publish the offer. Please try again.", 500);
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

if (!$stmt->execute()) {
    $errorNumber = $stmt->errno;
    error_log("Material insert failed: " . $stmt->error);
    $stmt->close();
    $conn->close();
    delete_material_image($imagePath);
    sendMaterialResponse(
        false,
        $errorNumber === 1452 ? "Select an existing company." : "Could not publish the offer. Please try again.",
        $errorNumber === 1452 ? 400 : 500
    );
}

$materialId = $stmt->insert_id;
$stmt->close();
$conn->close();
sendMaterialResponse(true, "Offer published successfully.", 200, [
    "material_id" => $materialId
]);
?>
