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

    $fullName = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $message = "Please complete all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {

        try {
            $db = new Database();
            $pdo = $db->connect();

            $check = $pdo->prepare(
                "SELECT customer_id FROM customers WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "An account with this email already exists.";
            } else {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO customers 
                    (full_name, email, phone, password)
                    VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([
                    $fullName,
                    $email,
                    $phone,
                    $hashedPassword
                ]);

                $message = "Registration successful! You can now log in.";
            }

        } catch (PDOException $e) {
            $message = "Registration failed. Please try again.";
        }
    }
}
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Registration - GlowWell</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>

    <div class="container">

<h1>GlowWell Beauty Clinic</h1>

<h2>Customer Registration</h2>

<?php if (!empty($message)): ?>
    <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

    <label>Full Name:</label><br>
    <input type="text" name="full_name" required>
    <br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required>
    <br><br>

    <label>Phone:</label><br>
    <input type="text" name="phone" required>
    <br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required>
    <br><br>

    <button type="submit">Register</button>

</form>

</div>

</body>
</html>