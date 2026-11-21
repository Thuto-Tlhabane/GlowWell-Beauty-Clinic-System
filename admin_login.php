<?php

session_start();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

require_once "config.php";

$db = new Database();
$pdo = $db->connect();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF protection */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {

        $message = "Invalid security token. Please refresh the page and try again.";

    } else {

        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        
    if (empty($email) || empty($password)) {
        $message = "Email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {

        $stmt = $pdo->prepare(
            "SELECT admin_id, full_name, email, password, role
             FROM admins
             WHERE email = ? AND status = 'Active'"
        );

        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin["password"])) {

            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["admin_id"];
            $_SESSION["admin_name"] = $admin["full_name"];
            $_SESSION["admin_role"] = $admin["role"];

            header("Location: admin_dashboard.php");
            exit;

        } else {
            $message = "Invalid admin email or password.";
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

    <title>Admin Login - GlowWell</title>

    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>

<body>

<header>
    <h1>GlowWell Beauty Clinic</h1>
    <p>Administration Management Portal</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="login.php">Customer Login</a>
    <a href="register.php">Register</a>
</nav>

<div class="container">

    <div class="card">

        <h2>Administrator Login</h2>

        <?php if (!empty($message)): ?>

            <div class="error">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
>

            <label for="email">Admin Email</label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

        <p>
            Customer?
            <a href="login.php">Customer Login</a>
        </p>

    </div>

</div>

<footer>
    <p>&copy; 2026 GlowWell Beauty Clinic. All rights reserved.</p>
</footer>

</body>
</html>