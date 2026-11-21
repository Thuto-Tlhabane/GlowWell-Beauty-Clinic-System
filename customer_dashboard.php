<?php

session_start();

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

$db = new Database();
$pdo = $db->connect();

$customerId = $_SESSION["customer_id"];

$stmt = $pdo->prepare(
    "SELECT full_name, email, phone
     FROM customers
     WHERE customer_id = ?"
);

$stmt->execute([$customerId]);

$customer = $stmt->fetch();

?>

<!DOCTYPE html>
<html>
    <link rel="stylesheet" href="style.css">
<head>
    <title>Customer Dashboard - GlowWell</title>
</head>

<body>

<header>
    <h1>GlowWell Beauty Clinic</h1>
    <p>Customer Management Portal</p>
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
        <h2>Customer Dashboard</h2>

        <p>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($customer["full_name"]); ?>
            </strong>
        </p>

        <p>
            Manage your GlowWell appointments and personal information
            from this dashboard.
        </p>
    </div>

    <div class="card">
        <h2>Your Profile</h2>

        <p>
            <strong>Email:</strong>
            <?php echo htmlspecialchars($customer["email"]); ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?php echo htmlspecialchars($customer["phone"]); ?>
        </p>

        <a href="update_profile.php" class="btn">
            Update Profile
        </a>
    </div>

    <div class="card">
        <h2>Customer Services</h2>

        <div class="stats">

            <div class="stat-card">
                <h3>Book</h3>
                <p>Schedule a new beauty or wellness appointment.</p>
                <a href="book_appointment.php" class="btn">
                    Book Appointment
                </a>
            </div>

            <div class="stat-card">
                <h3>Appointments</h3>
                <p>View your existing appointments and their status.</p>
                <a href="my_appointments.php" class="btn">
                    View Appointments
                </a>
            </div>

            <div class="stat-card">
                <h3>Profile</h3>
                <p>Update your personal information and password.</p>
                <a href="update_profile.php" class="btn">
                    Update Profile
                </a>
            </div>

        </div>
    </div>

</div>

<footer>
    <p>&copy; 2026 GlowWell Beauty Clinic. All rights reserved.</p>
</footer>

</body>
</html>