<?php

function save_material_image(array $file): ?string
{
    if ($file === []) {
        return null;
    }

    $error = $file["error"] ?? null;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (!is_int($error) || $error !== UPLOAD_ERR_OK) {
        throw new RuntimeException("The image upload failed. Please choose the image again.");
    }

    $size = $file["size"] ?? null;
    $temporaryPath = $file["tmp_name"] ?? null;
    if (!is_string($temporaryPath) || $temporaryPath === "" || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException("The image upload failed. Please choose the image again.");
    }
    if (!is_int($size) || $size < 0 || $size > 5 * 1024 * 1024) {
        throw new RuntimeException("Image size must not exceed 5 MB.");
    }

    $imageInfo = @getimagesize($temporaryPath);
    if ($imageInfo === false) {
        throw new RuntimeException("The selected file is not a valid image.");
    }

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif",
        "image/webp" => "webp"
    ];
    $mimeType = $imageInfo["mime"] ?? "";
    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException("Only JPG, PNG, GIF, and WEBP images are allowed.");
    }

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException("Could not create the uploads folder.");
    }
    if (!is_writable($uploadDir)) {
        throw new RuntimeException("The uploads folder is not writable.");
    }

    $fileName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
    $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($temporaryPath, $fullPath)) {
        throw new RuntimeException("Could not save the uploaded image.");
    }

    return "uploads/" . $fileName;
}

function delete_material_image(?string $relativePath): void
{
    if (
        $relativePath === null ||
        !preg_match('~^uploads/([A-Za-z0-9][A-Za-z0-9_.-]*)$~D', $relativePath, $matches)
    ) {
        return;
    }

    $uploadDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . "uploads");
    if ($uploadDir === false) {
        return;
    }

    $filePath = $uploadDir . DIRECTORY_SEPARATOR . $matches[1];
    if (is_file($filePath) && !unlink($filePath)) {
        error_log("Could not remove material image: " . basename($filePath));
    }
}
