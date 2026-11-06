<?php
session_start();
require_once __DIR__ . "/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please fill in all fields.";

    } else {

        $stmt = $conn->prepare(
            "SELECT admin_id, admin_name, password_hash
             FROM admins
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

    if ($password=== $row["password_hash"]) {

        session_regenerate_id(true);

        $_SESSION["admin_id"] = $row["admin_id"];
        $_SESSION["admin_name"] = $row["admin_name"];

        header("Location: ../admin.php");
        exit();

    } else {
        $error = "Admin found, but password hash does not match.";
    }

} else {
    $error = "Admin email was not found in database.";
}

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FAID Admin Login</title>

    <link rel="stylesheet" href="../CSS/adminLogin.css">
</head>

<body>

    <div class="login-container">

        <h1>FAID</h1>

        <h2>Admin Login</h2>

        <p>Welcome back, Administrator</p>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <label>Email Address</label>

            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <label>Password</label>

            <input
                type="password"
                name="password"
                placeholder="Enter your password"
                required
            >

            <button type="submit">Login</button>

        </form>

    </div>

</body>
</html>
