<?php
require_once __DIR__ . "/security.php";
require_once __DIR__ . "/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_is_valid()) {
        http_response_code(403);
        $error = "Your session token expired. Refresh the page and try again.";
    } else {

    $email = trim(post_string("email"));
    $password = post_string("password");

    if ($email === "" || $password === "") {

        $error = "Please fill in all fields.";

    } else {

        $stmt = $conn->prepare(
            "SELECT admin_id, admin_name, password_hash
             FROM admins
             WHERE email = ?"
        );

        if (!$stmt) {
            error_log("Admin login prepare failed: " . $conn->error);
            $error = "Login failed. Please try again.";
        } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $storedPassword = (string)$row["password_hash"];
            $passwordValid = password_verify($password, $storedPassword);
            $passwordInfo = password_get_info($storedPassword);
            $hasPasswordHash = $passwordInfo["algo"] !== null && $passwordInfo["algo"] !== 0;
            $legacyPassword = !$passwordValid &&
                !$hasPasswordHash &&
                hash_equals($storedPassword, $password);

            if ($passwordValid || $legacyPassword) {
                if ($legacyPassword || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upgrade = $conn->prepare(
                        "UPDATE admins SET password_hash = ? WHERE admin_id = ?"
                    );
                    if ($newHash !== false && $upgrade) {
                        $upgrade->bind_param("si", $newHash, $row["admin_id"]);
                        if (!$upgrade->execute()) {
                            error_log("Admin password upgrade failed: " . $upgrade->error);
                        }
                        $upgrade->close();
                    } else {
                        error_log("Could not prepare admin password upgrade.");
                    }
                }

                session_regenerate_id(true);
                unset($_SESSION["user_id"], $_SESSION["company_id"], $_SESSION["companyName"]);
                $_SESSION["admin_id"] = $row["admin_id"];
                $_SESSION["admin_name"] = $row["admin_name"];
                csrf_rotate_token();
                header("Location: ../admin.php");
                exit();
            }
        }

        $error = "Invalid email or password.";

        $stmt->close();
        }
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FAID Admin Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/adminLogin.css?v=20261116-cachefix-1025">
</head>

<body>

    <main class="auth-shell">
        <a class="auth-back" href="../home.html">← Back to FAID marketplace</a>
        <div class="admin-auth-grid">
            <aside class="admin-auth-aside">
                <a class="auth-logo" href="../home.html" aria-label="FAID homepage">
                    <img src="../Img/FaidLogo.png" alt="FAID logo">
                </a>
                <p class="auth-eyebrow">FAID ADMINISTRATION</p>
                <h1>Everything in its right place.</h1>
                <p>Manage company accounts, published offers, orders, and reporting from one focused workspace.</p>
                <div class="admin-auth-highlights"><span>Marketplace overview</span><span>Offer and company management</span><span>Order activity and reports</span></div>
                <span class="admin-auth-decoration" aria-hidden="true"></span>
            </aside>
            <section class="login-container" aria-labelledby="adminLoginTitle">
                <p class="auth-eyebrow">SECURE ADMIN ACCESS</p>
                <h2 id="adminLoginTitle">Welcome back</h2>
                <p class="auth-subtitle">Sign in to continue to your FAID workspace.</p>

                <?php if ($error !== ""): ?>
                    <div class="error" role="alert" aria-live="polite">
                        <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" data-loading-form>
                    <?php echo csrf_field(); ?>

                    <label for="adminEmail">Email address</label>
                    <input id="adminEmail" type="email" name="email" autocomplete="username" required>
                    <span class="field-error" aria-live="polite"></span>

                    <label for="adminPassword">Password</label>
                    <div class="password-field">
                        <input id="adminPassword" type="password" name="password" autocomplete="current-password" required>
                        <button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">Show</button>
                    </div>
                    <span class="field-error" aria-live="polite"></span>

                    <button class="auth-submit" type="submit">Sign in</button>
                </form>
                <p class="auth-security-note"><span aria-hidden="true">◆</span> Protected administrator sign-in</p>
            </section>
        </div>
    </main>
    <div id="toastRegion" class="toast-region" aria-live="polite" aria-atomic="true"></div>
    <script src="../JS/ui.js?v=20261116-cachefix-1025"></script>

</body>
</html>
