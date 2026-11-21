<?php

session_start();

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

require_once "config.php";

$db = new Database();
$pdo = $db->connect();

$message = "";

/* Get current customer details */
$stmt = $pdo->prepare(
    "SELECT
        full_name,
        email,
        phone
     FROM customers
     WHERE customer_id = ?"
);

$stmt->execute([
    $_SESSION["customer_id"]
]);

$customer = $stmt->fetch();

if (!$customer) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


/* Handle profile update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF protection */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {

        $message = "Invalid security token. Please refresh the page and try again.";

    } else {

        $fullName = trim($_POST["full_name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";


        /* Validate required fields */
        if (
            empty($fullName) ||
            empty($phone)
        ) {

            $message = "Please complete all required fields.";

        } elseif (
            !empty($newPassword) &&
            strlen($newPassword) < 6
        ) {

            $message = "The new password must be at least 6 characters.";

        } elseif (
            !empty($newPassword) &&
            $newPassword !== $confirmPassword
        ) {

            $message = "The new passwords do not match.";

        } else {

            try {

                /* Update password if supplied */
                if (!empty($newPassword)) {

                    $hashedPassword = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $pdo->prepare(
                        "UPDATE customers
                         SET full_name = ?,
                             phone = ?,
                             password = ?
                         WHERE customer_id = ?"
                    );

                    $stmt->execute([
                        $fullName,
                        $phone,
                        $hashedPassword,
                        $_SESSION["customer_id"]
                    ]);

                } else {

                    $stmt = $pdo->prepare(
                        "UPDATE customers
                         SET full_name = ?,
                             phone = ?
                         WHERE customer_id = ?"
                    );

                    $stmt->execute([
                        $fullName,
                        $phone,
                        $_SESSION["customer_id"]
                    ]);
                }


                /* Update session name */
                $_SESSION["customer_name"] = $fullName;

                $message = "Profile updated successfully.";


                /* Refresh displayed customer details */
                $customer["full_name"] = $fullName;
                $customer["phone"] = $phone;

            } catch (PDOException $e) {

                $message = "Unable to update your profile. Please try again.";
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

    <title>Update Profile - GlowWell</title>

    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>

</head>

<body>

<header>

    <h1>GlowWell Beauty Clinic</h1>

    <p>Customer Profile Portal</p>

</header>

<nav>

    <a href="customer_dashboard.php">Dashboard</a>

    <a href="book_appointment.php">Book Appointment</a>

    <a href="my_appointments.php">My Appointments</a>

    <a href="update_profile.php">My Profile</a>

    <a href="logout.php">Logout</a>

</nav>

<div class="container">

    <div class="card">

        <h2>Update Profile</h2>

        <p>Update your personal information and password below.</p>

    </div>


    <?php if (!empty($message)): ?>

        <div class="success">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <form method="POST">

        <input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
>

        <label>Full Name:</label>

        <input
            type="text"
            name="full_name"
            value="<?php echo htmlspecialchars($customer["full_name"]); ?>"
            required
        >


        <label>Email:</label>

        <input
            type="email"
            value="<?php echo htmlspecialchars($customer["email"]); ?>"
            disabled
        >


        <label>Phone Number:</label>

        <input
            type="text"
            name="phone"
            value="<?php echo htmlspecialchars($customer["phone"]); ?>"
            required
        >


        <label>New Password:</label>

        <input
            type="password"
            name="new_password"
            placeholder="Leave blank to keep current password"
        >


        <button type="submit">
            Update Profile
        </button>

    </form>


    <div class="card">

        <a href="customer_dashboard.php" class="btn btn-secondary">
            Back to Dashboard
        </a>

    </div>

</div>

<footer>

    <p>&copy; 2026 GlowWell Beauty Clinic. All rights reserved.</p>

</footer>

</body>

</html>