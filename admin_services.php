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

/* Add new service or update service status */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF protection */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid security token. Please refresh the page and try again.";

    } else {

        $action = $_POST["action"] ?? "";

        /* Add service */
        if ($action === "add_service") {

            $serviceName = trim($_POST["service_name"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $duration = $_POST["duration_minutes"] ?? "";
            $price = $_POST["price"] ?? "";

            if (
                empty($serviceName) ||
                empty($duration) ||
                empty($price)
            ) {

                $message = "Please complete all required service fields.";

            } elseif (
                !filter_var($duration, FILTER_VALIDATE_INT) ||
                (int)$duration <= 0
            ) {

                $message = "Please enter a valid service duration.";

            } elseif (
                !is_numeric($price) ||
                (float)$price < 0
            ) {

                $message = "Please enter a valid service price.";

            } else {

                try {

                    /* Check if service already exists */
                    $checkStmt = $pdo->prepare(
                        "SELECT service_id
                         FROM services
                         WHERE service_name = ?"
                    );

                    $checkStmt->execute([$serviceName]);

                    if ($checkStmt->fetch()) {

                        $message = "A service with this name already exists.";

                    } else {

                        /* Insert new service */
                        $stmt = $pdo->prepare(
                            "INSERT INTO services
                            (
                                service_name,
                                description,
                                duration_minutes,
                                price,
                                status
                            )
                            VALUES (?, ?, ?, ?, 'Available')"
                        );

                        $stmt->execute([
                            $serviceName,
                            $description,
                            (int)$duration,
                            (float)$price
                        ]);

                        $message = "Service added successfully.";
                    }

                } catch (PDOException $e) {

                    $message = "Unable to add the service.";
                }
            }

        /* Update service status */
        } elseif ($action === "update_status") {

            $serviceId = $_POST["service_id"] ?? "";
            $status = $_POST["status"] ?? "";

            $allowedStatuses = [
                "Available",
                "Unavailable"
            ];

            if (
                !filter_var($serviceId, FILTER_VALIDATE_INT) ||
                !in_array($status, $allowedStatuses, true)
            ) {

                $message = "Invalid service status update.";

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "UPDATE services
                         SET status = ?
                         WHERE service_id = ?"
                    );

                    $stmt->execute([
                        $status,
                        $serviceId
                    ]);

                    $message = "Service status updated successfully.";

                } catch (PDOException $e) {

                    $message = "Unable to update service status.";
                }
            }
        }
    }
}

/* Retrieve all services */
$stmt = $pdo->query(
    "SELECT
        service_id,
        service_name,
        description,
        duration_minutes,
        price,
        status
     FROM services
     ORDER BY service_name ASC"
);

$services = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Services - GlowWell</title>

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

        <h2>Manage Services</h2>

        <p>
            Add new beauty clinic services and manage the availability
            of existing services.
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


    <!-- Add Service -->

    <div class="card">

        <h2>Add New Service</h2>


        <form method="POST">


            <!-- Action -->

            <input
                type="hidden"
                name="action"
                value="add_service"
            >


            <!-- CSRF protection -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
            >


            <label for="service_name">
                Service Name
            </label>

            <input
                type="text"
                id="service_name"
                name="service_name"
                required
            >


            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
            ></textarea>


            <label for="duration_minutes">
                Duration (Minutes)
            </label>

            <input
                type="number"
                id="duration_minutes"
                name="duration_minutes"
                min="1"
                required
            >


            <label for="price">
                Price (R)
            </label>

            <input
                type="number"
                id="price"
                name="price"
                min="0"
                step="0.01"
                required
            >


            <button type="submit">
                Add Service
            </button>


        </form>

    </div>


    <!-- Current Services -->

    <div class="card">

        <h2>Current Services</h2>


        <div class="table-container">


            <table>


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Service Name</th>

                        <th>Description</th>

                        <th>Duration</th>

                        <th>Price</th>

                        <th>Status</th>

                        <th>Update Status</th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($services as $service): ?>


                    <tr>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $service["service_id"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $service["service_name"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $service["description"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $service["duration_minutes"]
                            );
                            ?>
                            minutes

                        </td>


                        <td>

                            R<?php
                            echo number_format(
                                (float)$service["price"],
                                2
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $service["status"]
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


                                <!-- Service ID -->

                                <input
                                    type="hidden"
                                    name="service_id"
                                    value="<?php echo htmlspecialchars($service["service_id"]); ?>"
                                >


                                <select name="status">


                                    <option
                                        value="Available"
                                        <?php
                                        if (
                                            $service["status"]
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
                                            $service["status"]
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