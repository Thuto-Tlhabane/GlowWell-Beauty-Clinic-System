<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

require_once "config.php";

$db = new Database();
$pdo = $db->connect();

/* BASIC SYSTEM TOTALS */
$totalCustomers = $pdo->query(
    "SELECT COUNT(*) FROM customers"
)->fetchColumn();

$totalAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments"
)->fetchColumn();

$completedAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'Completed'"
)->fetchColumn();

$pendingAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'Pending'"
)->fetchColumn();

$confirmedAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'Confirmed'"
)->fetchColumn();

$cancelledAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'Cancelled'"
)->fetchColumn();


/* ESTIMATED REVENUE */
$revenueStmt = $pdo->query(
    "SELECT COALESCE(SUM(s.price), 0)
     FROM appointments a
     INNER JOIN services s
     ON a.service_id = s.service_id
     WHERE a.status = 'Completed'"
);

$estimatedRevenue = $revenueStmt->fetchColumn();


/* MOST BOOKED SERVICES */
$serviceStmt = $pdo->query(
    "SELECT
        s.service_name,
        COUNT(a.appointment_id) AS booking_count
     FROM services s
     LEFT JOIN appointments a
     ON s.service_id = a.service_id
     GROUP BY s.service_id, s.service_name
     ORDER BY booking_count DESC, s.service_name ASC"
);

$serviceReport = $serviceStmt->fetchAll();


/* STAFF APPOINTMENT REPORT */
$staffStmt = $pdo->query(
    "SELECT
        st.full_name,
        st.role,
        COUNT(a.appointment_id) AS appointment_count
     FROM staff st
     LEFT JOIN appointments a
     ON st.staff_id = a.staff_id
     GROUP BY st.staff_id, st.full_name, st.role
     ORDER BY appointment_count DESC, st.full_name ASC"
);

$staffReport = $staffStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Reports - GlowWell</title>
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
        <h2>Business Reports</h2>
        <p>
            View important business information, appointment statistics,
            popular services and staff appointment activity.
        </p>
    </div>

    <div class="card">
        <h2>System Summary</h2>

        <div class="stats">

            <div class="stat-card">
                <h3><?php echo $customerCount; ?></h3>
                <p>Total Customers</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $staffCount; ?></h3>
                <p>Active Staff</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $serviceCount; ?></h3>
                <p>Available Services</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $appointmentCount; ?></h3>
                <p>Total Appointments</p>
            </div>

        </div>
    </div>

    <div class="card">
        <h2>Appointment Status</h2>

        <div class="stats">

            <div class="stat-card">
                <h3><?php echo $pendingCount; ?></h3>
                <p>Pending</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $confirmedCount; ?></h3>
                <p>Confirmed</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $completedCount; ?></h3>
                <p>Completed</p>
            </div>

            <div class="stat-card">
                <h3><?php echo $cancelledCount; ?></h3>
                <p>Cancelled</p>
            </div>

        </div>
    </div>

    <div class="card">

        <h2>Estimated Completed Revenue</h2>

        <div class="stat-card">
            <h3>
                R<?php echo number_format($completedRevenue, 2); ?>
            </h3>
            <p>Revenue from completed appointments</p>
        </div>

    </div>

    <div class="card">

        <h2>Most Booked Services</h2>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Number of Bookings</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($popularServices as $service): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($service["service_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($service["booking_count"]); ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

    <div class="card">

        <h2>Staff Appointment Report</h2>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Role</th>
                        <th>Number of Appointments</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($staffReports as $report): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($report["full_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($report["role"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($report["appointment_count"]); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<footer>
    <p>&copy; 2026 GlowWell Beauty Clinic. All rights reserved.</p>
</footer>

</body>
</html>