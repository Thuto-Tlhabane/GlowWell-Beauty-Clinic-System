<?php

session_start();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

        if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid security token. Please refresh the page and try again.";
    } else {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";

    } else {

        try {

            $db = new Database();
            $pdo = $db->connect();

            $stmt = $pdo->prepare(
                "SELECT customer_id, full_name, email, password
                 FROM customers
                 WHERE email = ?"
            );

            $stmt->execute([$email]);

            $customer = $stmt->fetch();

            if ($customer && password_verify($password, $customer["password"])) {

    session_regenerate_id(true);

    $_SESSION["customer_id"] = $customer["customer_id"];
    $_SESSION["customer_name"] = $customer["full_name"];

    header("Location: customer_dashboard.php");
    exit;

            } else {

                $message = "Invalid email or password.";
            }

        } catch (PDOException $e) {

            $message = "Login failed. Please try again.";
        }
    }
}
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - GlowWell</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>

<body>

<body>

<header>
    <h1>GlowWell Beauty Clinic</h1>
    <p>Customer Login Portal</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
    <a href="login.php">Customer Login</a>
    <a href="admin_login.php">Staff/Admin Login</a>
</nav>

<div class="container">

    <div class="card">

        <h2>Customer Login</h2>

        <?php if (!empty($message)): ?>

            <div class="error">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <label>Email:</label>
            <input
                type="email"
                name="email"
                required
            >

            <label>Password:</label>
            <input
                type="password"
                name="password"
                required
            >

            <button type="submit">Login</button>

        </form>

        <p>
            Don't have an account?
            <a href="register.php" class="btn">
                Create an Account
            </a>
        </p>

    </div>

</div>

<footer>
    <p>&copy; 2026 GlowWell Beauty Clinic. All rights reserved.</p>
</footer>
</body>
</html>