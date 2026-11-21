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

/* Get available services */
$serviceStmt = $pdo->query(
    "SELECT service_id, service_name, duration_minutes, price
     FROM services
     WHERE status = 'Available'
     ORDER BY service_name"
);

$services = $serviceStmt->fetchAll();

/* Get active staff */
$staffStmt = $pdo->query(
    "SELECT staff_id, full_name, role
     FROM staff
     WHERE status = 'Active'
     ORDER BY full_name"
);

$staff = $staffStmt->fetchAll();

/* Handle booking */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF protection */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid security token. Please refresh the page and try again.";

    } else {

        /* Get submitted form data */
        $staffId = $_POST["staff_id"] ?? "";
        $serviceId = $_POST["service_id"] ?? "";
        $appointmentDate = $_POST["appointment_date"] ?? "";
        $appointmentTime = $_POST["appointment_time"] ?? "";
        $notes = trim($_POST["notes"] ?? "");

        /* Basic validation */
        if (
            empty($staffId) ||
            empty($serviceId) ||
            empty($appointmentDate) ||
            empty($appointmentTime)
        ) {

            $message = "Please complete all required fields.";

        } elseif ($appointmentDate < date("Y-m-d")) {

            $message = "Please select a future appointment date.";

        } else {

            try {

                /* Determine day of week */
                $dayOfWeek = date("l", strtotime($appointmentDate));

                /* Get selected service duration */
                $durationStmt = $pdo->prepare(
                    "SELECT duration_minutes
                     FROM services
                     WHERE service_id = ?
                     AND status = 'Available'"
                );

                $durationStmt->execute([$serviceId]);

                $service = $durationStmt->fetch();

                if (!$service) {

                    $message = "The selected service is not available.";

                } else {

                    $serviceDuration = (int)$service["duration_minutes"];

                    /* Check staff schedule */
                    $scheduleStmt = $pdo->prepare(
                        "SELECT start_time, end_time
                         FROM staff_schedules
                         WHERE staff_id = ?
                         AND day_of_week = ?
                         AND status = 'Available'
                         LIMIT 1"
                    );

                    $scheduleStmt->execute([
                        $staffId,
                        $dayOfWeek
                    ]);

                    $schedule = $scheduleStmt->fetch();

                    if (!$schedule) {

                        $message = "The selected staff member is not available on this day.";

                    } else {

                        /* Create appointment start and end times */
                        $appointmentStart = new DateTime(
                            $appointmentDate . " " . $appointmentTime
                        );

                        $scheduleStart = new DateTime(
                            $appointmentDate . " " . $schedule["start_time"]
                        );

                        $scheduleEnd = new DateTime(
                            $appointmentDate . " " . $schedule["end_time"]
                        );

                        $appointmentEnd = clone $appointmentStart;

                        $appointmentEnd->modify(
                            "+" . $serviceDuration . " minutes"
                        );

                        /* Check working hours */
                        if (
                            $appointmentStart < $scheduleStart ||
                            $appointmentEnd > $scheduleEnd
                        ) {

                            $message = "The appointment time and service duration fall outside the staff member's working hours.";

                        } else {

                            /* Check for overlapping appointments */
                            $checkStmt = $pdo->prepare(
                                "SELECT a.appointment_id,
                                        a.appointment_time,
                                        s.duration_minutes
                                 FROM appointments a
                                 INNER JOIN services s
                                    ON a.service_id = s.service_id
                                 WHERE a.staff_id = ?
                                 AND a.appointment_date = ?
                                 AND a.status IN ('Pending', 'Confirmed')"
                            );

                            $checkStmt->execute([
                                $staffId,
                                $appointmentDate
                            ]);

                            $existingAppointments = $checkStmt->fetchAll();

                            $hasConflict = false;

                            foreach ($existingAppointments as $existing) {

                                $existingStart = new DateTime(
                                    $appointmentDate . " " . $existing["appointment_time"]
                                );

                                $existingEnd = clone $existingStart;

                                $existingEnd->modify(
                                    "+" . (int)$existing["duration_minutes"] . " minutes"
                                );

                                if (
                                    $appointmentStart < $existingEnd &&
                                    $appointmentEnd > $existingStart
                                ) {

                                    $hasConflict = true;
                                    break;
                                }
                            }

                            if ($hasConflict) {

                                $message = "The selected staff member already has an appointment that overlaps with this time.";

                            } else {

                                /* Create appointment */
                                $stmt = $pdo->prepare(
                                    "INSERT INTO appointments
                                    (
                                        customer_id,
                                        staff_id,
                                        service_id,
                                        appointment_date,
                                        appointment_time,
                                        status,
                                        notes
                                    )
                                    VALUES (?, ?, ?, ?, ?, 'Pending', ?)"
                                );

                                $stmt->execute([
                                    $_SESSION["customer_id"],
                                    $staffId,
                                    $serviceId,
                                    $appointmentDate,
                                    $appointmentTime,
                                    $notes
                                ]);

                                $message = "Appointment booked successfully!";
                            }
                        }
                    }
                }

            } catch (PDOException $e) {

                $message = "Unable to book the appointment. Please try again.";
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

    <title>Book Appointment - GlowWell</title>

    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>

</head>

<body>

<header>

    <h1>GlowWell Beauty Clinic</h1>

    <p>Customer Booking Portal</p>

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

        <h2>Book an Appointment</h2>

        <?php if (!empty($message)): ?>

            <div class="success">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <label>Staff Member:</label>

            <select name="staff_id" required>

                <option value="">-- Select Staff Member --</option>

                <?php foreach ($staff as $member): ?>

                    <option value="<?php echo $member["staff_id"]; ?>">

                        <?php

                        echo htmlspecialchars(

                            $member["full_name"] . " - " . $member["role"]

                        );

                        ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <label>Service:</label>

            <select name="service_id" required>

                <option value="">-- Select Service --</option>

                <?php foreach ($services as $service): ?>

                    <option value="<?php echo $service["service_id"]; ?>">

                        <?php

                        echo htmlspecialchars(

                            $service["service_name"]

                            . " - R"

                            . number_format($service["price"], 2)

                        );

                        ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <label>Appointment Date:</label>

            <input

                type="date"

                name="appointment_date"

                min="<?php echo date('Y-m-d'); ?>"

                required

            >


            <label>Appointment Time:</label>

            <input

                type="time"

                name="appointment_time"

                required

            >


            <label>Notes:</label>

            <textarea

                name="notes"

                rows="4"

                placeholder="Optional notes or special requests"

            ></textarea>


            <button type="submit">

                Book Appointment

            </button>

        </form>


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