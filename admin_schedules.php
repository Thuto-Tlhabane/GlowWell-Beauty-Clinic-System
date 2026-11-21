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

/* Handle schedule actions */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF protection */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid security token. Please refresh the page and try again.";

    } else {

        $action = $_POST["action"] ?? "";

        /* Add schedule */
        if ($action === "add_schedule") {

            $staffId = $_POST["staff_id"] ?? "";
            $dayOfWeek = $_POST["day_of_week"] ?? "";
            $startTime = $_POST["start_time"] ?? "";
            $endTime = $_POST["end_time"] ?? "";

            $allowedDays = [
                "Monday",
                "Tuesday",
                "Wednesday",
                "Thursday",
                "Friday",
                "Saturday",
                "Sunday"
            ];

            if (
                !filter_var($staffId, FILTER_VALIDATE_INT) ||
                !in_array($dayOfWeek, $allowedDays, true) ||
                empty($startTime) ||
                empty($endTime)
            ) {

                $message = "Please complete all schedule fields.";

            } elseif ($startTime >= $endTime) {

                $message = "The start time must be earlier than the end time.";

            } else {

                try {

                    /* Check that staff exists */
                    $staffCheck = $pdo->prepare(
                        "SELECT staff_id
                         FROM staff
                         WHERE staff_id = ?
                         AND status = 'Active'"
                    );

                    $staffCheck->execute([$staffId]);

                    if (!$staffCheck->fetch()) {

                        $message = "The selected staff member is not active.";

                    } else {

                        /* Check for duplicate schedule */
                        $duplicateCheck = $pdo->prepare(
                            "SELECT schedule_id
                             FROM staff_schedules
                             WHERE staff_id = ?
                             AND day_of_week = ?"
                        );

                        $duplicateCheck->execute([
                            $staffId,
                            $dayOfWeek
                        ]);

                        if ($duplicateCheck->fetch()) {

                            $message = "A schedule already exists for this staff member on this day.";

                        } else {

                            /* Insert schedule */
                            $stmt = $pdo->prepare(
                                "INSERT INTO staff_schedules
                                (
                                    staff_id,
                                    day_of_week,
                                    start_time,
                                    end_time,
                                    status
                                )
                                VALUES (?, ?, ?, ?, 'Available')"
                            );

                            $stmt->execute([
                                $staffId,
                                $dayOfWeek,
                                $startTime,
                                $endTime
                            ]);

                            $message = "Staff schedule added successfully.";
                        }
                    }

                } catch (PDOException $e) {

                    $message = "Unable to add the staff schedule.";
                }
            }

        /* Update schedule status */
        } elseif ($action === "update_status") {

            $scheduleId = $_POST["schedule_id"] ?? "";
            $status = $_POST["status"] ?? "";

            $allowedStatuses = [
                "Available",
                "Unavailable"
            ];

            if (
                !filter_var($scheduleId, FILTER_VALIDATE_INT) ||
                !in_array($status, $allowedStatuses, true)
            ) {

                $message = "Invalid schedule status update.";

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "UPDATE staff_schedules
                         SET status = ?
                         WHERE schedule_id = ?"
                    );

                    $stmt->execute([
                        $status,
                        $scheduleId
                    ]);

                    $message = "Schedule status updated successfully.";

                } catch (PDOException $e) {

                    $message = "Unable to update schedule status.";
                }
            }
        }
    }
}


/* Retrieve active staff */
$staffStmt = $pdo->query(
    "SELECT
        staff_id,
        full_name
     FROM staff
     WHERE status = 'Active'
     ORDER BY full_name ASC"
);

$staff = $staffStmt->fetchAll();


/* Retrieve schedules */
$scheduleStmt = $pdo->query(
    "SELECT
        ss.schedule_id,
        ss.staff_id,
        s.full_name,
        ss.day_of_week,
        ss.start_time,
        ss.end_time,
        ss.status
     FROM staff_schedules ss
     INNER JOIN staff s
        ON ss.staff_id = s.staff_id
     ORDER BY s.full_name ASC,
              FIELD(
                  ss.day_of_week,
                  'Monday',
                  'Tuesday',
                  'Wednesday',
                  'Thursday',
                  'Friday',
                  'Saturday',
                  'Sunday'
              )"
);

$schedules = $scheduleStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Staff Schedules - GlowWell</title>

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


    <!-- Page introduction -->

    <div class="card">

        <h2>Staff Schedules</h2>

        <p>
            Add and manage staff working schedules.
        </p>

    </div>


    <!-- Message -->

    <?php if (!empty($message)): ?>

        <div class="<?php echo (
            strpos($message, "successfully") !== false
        ) ? "success" : "error"; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- Add Schedule -->

    <div class="card">

        <h2>Add Staff Schedule</h2>


        <form method="POST">


            <!-- Action -->

            <input
                type="hidden"
                name="action"
                value="add_schedule"
            >


            <!-- CSRF protection -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
            >


            <label for="staff_id">
                Staff Member
            </label>

            <select
                id="staff_id"
                name="staff_id"
                required
            >

                <option value="">
                    Select Staff Member
                </option>

                <?php foreach ($staff as $member): ?>

                    <option
                        value="<?php echo htmlspecialchars($member["staff_id"]); ?>"
                    >

                        <?php echo htmlspecialchars($member["full_name"]); ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <label for="day_of_week">
                Day of Week
            </label>

            <select
                id="day_of_week"
                name="day_of_week"
                required
            >

                <option value="">
                    Select Day
                </option>

                <option value="Monday">
                    Monday
                </option>

                <option value="Tuesday">
                    Tuesday
                </option>

                <option value="Wednesday">
                    Wednesday
                </option>

                <option value="Thursday">
                    Thursday
                </option>

                <option value="Friday">
                    Friday
                </option>

                <option value="Saturday">
                    Saturday
                </option>

                <option value="Sunday">
                    Sunday
                </option>

            </select>


            <label for="start_time">
                Start Time
            </label>

            <input
                type="time"
                id="start_time"
                name="start_time"
                required
            >


            <label for="end_time">
                End Time
            </label>

            <input
                type="time"
                id="end_time"
                name="end_time"
                required
            >


            <button type="submit">
                Add Schedule
            </button>


        </form>

    </div>


    <!-- Current Schedules -->

    <div class="card">

        <h2>Current Staff Schedules</h2>


        <div class="table-container">


            <table>


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Staff Member</th>

                        <th>Day</th>

                        <th>Start Time</th>

                        <th>End Time</th>

                        <th>Status</th>

                        <th>Update Status</th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($schedules as $schedule): ?>


                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $schedule["schedule_id"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $schedule["full_name"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $schedule["day_of_week"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $schedule["start_time"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $schedule["end_time"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $schedule["status"]
                            );
                            ?>

                        </td>


                        <td>


                            <form method="POST">


                                <!-- Action -->

                                <input
                                    type="hidden"
                                    name="action"
                                    value="update_status"
                                >


                                <!-- CSRF protection -->

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                                >


                                <!-- Schedule ID -->

                                <input
                                    type="hidden"
                                    name="schedule_id"
                                    value="<?php echo htmlspecialchars($schedule["schedule_id"]); ?>"
                                >


                                <select name="status">


                                    <option
                                        value="Available"
                                        <?php
                                        if (
                                            $schedule["status"]
                                            === "Available"
                                        ) {
                                            echo "selected";
                                        }
                                        ?>
                                    >
                                        Available
                                    </option>


                                    <option
                                        value="Unavailable"
                                        <?php
                                        if (
                                            $schedule["status"]
                                            === "Unavailable"
                                        ) {
                                            echo "selected";
                                        }
                                        ?>
                                    >
                                        Unavailable
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

    <p>
        &copy; 2026 GlowWell Beauty Clinic.
        All rights reserved.
    </p>

</footer>


</body>

</html>