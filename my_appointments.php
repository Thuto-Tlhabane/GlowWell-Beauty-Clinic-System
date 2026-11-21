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

/* Get appointments for the logged-in customer */
$stmt = $pdo->prepare(
    "SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.notes,
        s.full_name AS staff_name,
        s.role,
        sv.service_name,
        sv.price,
        sv.duration_minutes
     FROM appointments a
     INNER JOIN staff s ON a.staff_id = s.staff_id
     INNER JOIN services sv ON a.service_id = sv.service_id
     WHERE a.customer_id = ?
     ORDER BY a.appointment_date DESC, a.appointment_time DESC"
);

$stmt->execute([$customerId]);
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Appointments - GlowWell</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<header>

    <h1>GlowWell Beauty Clinic</h1>

    <p>Customer Appointment Portal</p>

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

        <h2>My Appointments</h2>

        <p>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($_SESSION["customer_name"]); ?>
            </strong>
        </p>

    </div>


    <?php if (count($appointments) > 0): ?>

        <div class="card">

            <h2>Appointment History</h2>

            <div class="table-container">

                <table>

                    <tr>

                        <th>Date</th>

                        <th>Time</th>

                        <th>Service</th>

                        <th>Staff Member</th>

                        <th>Duration</th>

                        <th>Price</th>

                        <th>Status</th>

                        <th>Notes</th>

                    </tr>


                    <?php foreach ($appointments as $appointment): ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $appointment["appointment_date"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                substr($appointment["appointment_time"], 0, 5)
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $appointment["service_name"]
                            );
                            ?>
                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $appointment["staff_name"]
                            );
                            ?>

                            <br>

                            <small>
                                <?php
                                echo htmlspecialchars(
                                    $appointment["role"]
                                );
                                ?>
                            </small>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $appointment["duration_minutes"]
                            );
                            ?>

                            minutes

                        </td>

                        <td>

                            R<?php
                            echo number_format(
                                $appointment["price"],
                                2
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $appointment["status"]
                            );
                            ?>

                        </td>

                        <td>

                            <?php

                            echo !empty($appointment["notes"])
                                ? htmlspecialchars($appointment["notes"])
                                : "None";

                            ?>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                </table>

            </div>

        </div>

    <?php else: ?>

        <div class="card">

            <h2>No Appointments</h2>

            <p>You do not have any appointments yet.</p>

        </div>

    <?php endif; ?>


    <div class="card">

        <a href="book_appointment.php" class="btn">
            Book Another Appointment
        </a>

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