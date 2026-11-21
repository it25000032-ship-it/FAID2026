<?php
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}

fwrite(STDOUT, "Enter the password to hash: ");
$password = trim((string)fgets(STDIN));
if ($password === "") {
    fwrite(STDERR, "A non-empty password is required.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Could not create the password hash.\n");
    exit(1);
}

fwrite(STDOUT, $hash . PHP_EOL);
?>