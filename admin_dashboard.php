<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

require_once "config.php";

$db = new Database();
$pdo = $db->connect();

$adminName = $_SESSION["admin_name"];

$customerCount = $pdo->query(
    "SELECT COUNT(*) FROM customers"
)->fetchColumn();

$staffCount = $pdo->query(
    "SELECT COUNT(*) FROM staff WHERE status = 'Active'"
)->fetchColumn();

$serviceCount = $pdo->query(
    "SELECT COUNT(*) FROM services WHERE status = 'Available'"
)->fetchColumn();

$appointmentCount = $pdo->query(
    "SELECT COUNT(*) FROM appointments"
)->fetchColumn();

$pendingCount = $pdo->query(
    "SELECT COUNT(*) FROM appointments WHERE status = 'Pending'"
)->fetchColumn();

$confirmedCount = $pdo->query(
    "SELECT COUNT(*) FROM appointments WHERE status = 'Confirmed'"
)->fetchColumn();

$completedCount = $pdo->query(
    "SELECT COUNT(*) FROM appointments WHERE status = 'Completed'"
)->fetchColumn();

$cancelledCount = $pdo->query(
    "SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled'"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - GlowWell</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<header>

    <h1>GlowWell Beauty Clinic</h1>

    <p>Administration Management Portal</p>

</header>

<nav>

    <a href="admin_dashboard.php">Dashboard</a>

    <a href="admin_appointments.php">Appointments</a>

    <a href="admin_staff.php">Staff</a>

    <a href="admin_services.php">Services</a>

    <a href="admin_reports.php">Reports</a>

    <a href="admin_schedules.php">Schedules</a>

    <a href="admin_logout.php">Logout</a>

</nav>

<div class="container">

    <div class="card">

        <h2>Administrator Dashboard</h2>

        <p>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($adminName); ?>
            </strong>
        </p>

        <p>
            Manage GlowWell Beauty Clinic's appointments,
            staff, services and business information.
        </p>

    </div>


    <div class="card">

        <h2>System Overview</h2>

        <div class="stats">

            <div class="stat-card">

                <h3>
                    <?php echo $customerCount; ?>
                </h3>

                <p>Total Customers</p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $staffCount; ?>
                </h3>

                <p>Active Staff</p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $serviceCount; ?>
                </h3>

                <p>Available Services</p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $appointmentCount; ?>
                </h3>

                <p>Total Appointments</p>

            </div>

        </div>

    </div>


    <div class="card">

        <h2>Appointment Status</h2>

        <div class="stats">

            <div class="stat-card">

                <h3>
                    <?php echo $pendingCount; ?>
                </h3>

                <p>Pending</p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $confirmedCount; ?>
                </h3>

                <p>Confirmed</p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $completedCount; ?>
                </h3>

                <p>Completed</p>

            </div>


            <div class="stat-card">

                <h3>
                    <?php echo $cancelledCount; ?>
                </h3>

                <p>Cancelled</p>

            </div>

        </div>

    </div>


    <div class="card">

        <h2>Administration</h2>

        <p>
            <a href="admin_appointments.php" class="btn">
                Manage Appointments
            </a>
        </p>

        <p>
            <a href="admin_staff.php" class="btn">
                Manage Staff
            </a>
        </p>

        <p>
            <a href="admin_services.php" class="btn">
                Manage Services
            </a>
        </p>

        <p>
            <a href="admin_reports.php" class="btn">
                View Reports
            </a>
        </p>

        <p>
            <a href="admin_schedules.php" class="btn">
                Manage Staff Schedules
            </a>
        </p>

    </div>

</div>

<footer>

    <p>&copy; 2026 GlowWell Beauty Clinic. All rights reserved.</p>

</footer>

</body>

</html>