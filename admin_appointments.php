<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

require_once "config.php";

$db = new Database();
$pdo = $db->connect();

$message = "";

/* Update appointment status */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF protection */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid security token. Please refresh the page and try again.";

    } else {

        $appointmentId = $_POST["appointment_id"] ?? "";
        $status = $_POST["status"] ?? "";

        $allowedStatuses = [
            "Pending",
            "Confirmed",
            "Completed",
            "Cancelled"
        ];

        /* Validate appointment update */
        if (
            !filter_var($appointmentId, FILTER_VALIDATE_INT) ||
            !in_array($status, $allowedStatuses, true)
        ) {

            $message = "Invalid appointment update.";

        } else {

            try {

                $stmt = $pdo->prepare(
                    "UPDATE appointments
                     SET status = ?
                     WHERE appointment_id = ?"
                );

                $stmt->execute([
                    $status,
                    $appointmentId
                ]);

                $message = "Appointment status updated successfully.";

            } catch (PDOException $e) {

                $message = "Unable to update the appointment.";

            }
        }
    }
}

/* Retrieve all appointments */
$stmt = $pdo->query(
    "SELECT
        a.appointment_id,
        c.full_name AS customer_name,
        c.email AS customer_email,
        s.full_name AS staff_name,
        sv.service_name,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.notes
     FROM appointments a
     INNER JOIN customers c
        ON a.customer_id = c.customer_id
     INNER JOIN staff s
        ON a.staff_id = s.staff_id
     INNER JOIN services sv
        ON a.service_id = sv.service_id
     ORDER BY a.appointment_date ASC,
              a.appointment_time ASC"
);

$appointments = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - GlowWell</title>
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
        <h2>Manage Appointments</h2>

        <p>
            View customer appointments and update their booking status.
        </p>
    </div>

    <div class="card">

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Staff</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Update Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($appointments as $appointment): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($appointment["appointment_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($appointment["customer_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($appointment["staff_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($appointment["service_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($appointment["appointment_date"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($appointment["appointment_time"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($appointment["status"]); ?>
                        </td>

                        <td>

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
>

                                <input
                                    type="hidden"
                                    name="appointment_id"
                                    value="<?php echo htmlspecialchars($appointment["appointment_id"]); ?>"
                                >

                                <select name="status">

                                    <option value="Pending"
                                        <?php if ($appointment["status"] === "Pending") echo "selected"; ?>>
                                        Pending
                                    </option>

                                    <option value="Confirmed"
                                        <?php if ($appointment["status"] === "Confirmed") echo "selected"; ?>>
                                        Confirmed
                                    </option>

                                    <option value="Completed"
                                        <?php if ($appointment["status"] === "Completed") echo "selected"; ?>>
                                        Completed
                                    </option>

                                    <option value="Cancelled"
                                        <?php if ($appointment["status"] === "Cancelled") echo "selected"; ?>>
                                        Cancelled
                                    </option>

                                </select>

                                <button type="submit">
                                    Update
                                </button>

                            </form>

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