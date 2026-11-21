
<?php

session_start();

/* Check administrator login */
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

/* Generate CSRF token */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

require_once "config.php";

/* Database connection */
$db = new Database();
$pdo = $db->connect();

$message = "";

/* Add new staff or update staff status */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* CSRF protection */
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {

        $message = "Invalid security token. Please refresh the page and try again.";

    } else {

        $action = $_POST["action"] ?? "";

        /* =====================================
           ADD NEW STAFF MEMBER
        ===================================== */

        if ($action === "add_staff") {

            $fullName = trim($_POST["full_name"] ?? "");
            $role = trim($_POST["role"] ?? "");

            /* Validate required fields */
            if (empty($fullName) || empty($role)) {

                $message = "Please complete all staff fields.";

            } else {

                try {

                    /* Insert new staff member */
                    $stmt = $pdo->prepare(
                        "INSERT INTO staff
                        (
                            full_name,
                            role,
                            status
                        )
                        VALUES (?, ?, 'Active')"
                    );

                    $stmt->execute([
                        $fullName,
                        $role
                    ]);

                    $message = "Staff member added successfully.";

                } catch (PDOException $e) {

                    $message = "Unable to add the staff member.";
                }
            }

        /* =====================================
           UPDATE STAFF STATUS
        ===================================== */

        } elseif ($action === "update_status") {

            $staffId = $_POST["staff_id"] ?? "";
            $status = $_POST["status"] ?? "";

            $allowedStatuses = [
                "Active",
                "Inactive"
            ];

            /* Validate staff ID and status */
            if (
                !filter_var($staffId, FILTER_VALIDATE_INT) ||
                !in_array($status, $allowedStatuses, true)
            ) {

                $message = "Invalid staff status update.";

            } else {

                try {

                    $stmt = $pdo->prepare(
                        "UPDATE staff
                         SET status = ?
                         WHERE staff_id = ?"
                    );

                    $stmt->execute([
                        $status,
                        $staffId
                    ]);

                    $message = "Staff status updated successfully.";

                } catch (PDOException $e) {

                    $message = "Unable to update staff status.";
                }
            }

        } else {

            $message = "Invalid request.";
        }
    }
}

/* =====================================
   RETRIEVE ALL STAFF MEMBERS
===================================== */

$stmt = $pdo->query(
    "SELECT
        staff_id,
        full_name,
        role,
        status
     FROM staff
     ORDER BY full_name ASC"
);

$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Staff - GlowWell</title>

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

        <h2>Manage Staff</h2>

        <p>
            Add new staff members and manage the status of existing staff.
        </p>

    </div>


    <!-- Display messages -->

    <?php if (!empty($message)): ?>

        <div class="<?php echo (
            strpos($message, "successfully") !== false
        ) ? "success" : "error"; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- Add New Staff -->

    <div class="card">

        <h2>Add New Staff Member</h2>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="add_staff"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
            >

            <label for="full_name">
                Full Name
            </label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                required
            >

            <label for="role">
                Role
            </label>

            <input
                type="text"
                id="role"
                name="role"
                required
            >

            <button type="submit">
                Add Staff
            </button>

        </form>

    </div>


    <!-- Current Staff -->

    <div class="card">

        <h2>Current Staff</h2>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Full Name</th>

                        <th>Role</th>

                        <th>Status</th>

                        <th>Update Status</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (!empty($staff)): ?>

                        <?php foreach ($staff as $member): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($member["staff_id"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($member["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($member["role"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($member["status"]); ?>
                                </td>

                                <td>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update_status"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="staff_id"
                                            value="<?php echo htmlspecialchars($member["staff_id"]); ?>"
                                        >

                                        <select name="status">

                                            <option
                                                value="Active"
                                                <?php
                                                if ($member["status"] === "Active") {
                                                    echo "selected";
                                                }
                                                ?>
                                            >
                                                Active
                                            </option>

                                            <option
                                                value="Inactive"
                                                <?php
                                                if ($member["status"] === "Inactive") {
                                                    echo "selected";
                                                }
                                                ?>
                                            >
                                                Inactive
                                            </option>

                                        </select>

                                        <button type="submit">
                                            Update
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="5">
                                No staff members found.
                            </td>

                        </tr>

                    <?php endif; ?>

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
