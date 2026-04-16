<?php
session_start();
include("db.php");

// Only allow admins
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all bookings
$stmt = $conn->prepare("SELECT b.*, u.full_name AS customer_name 
                        FROM bookings b
                        JOIN users u ON b.user_id = u.id
                        ORDER BY b.id DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings - Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
        <span>Admin Dashboard</span>
    </div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_bookings.php">Manage Bookings</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="logout_admin.php">Logout</a>
    </nav>
</header>

<section class="hero" style="padding: 40px;">
    <h2>All Bookings</h2>
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Service</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($row['service']); ?></td>
            <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
            <td>
                <a href="update_booking.php?id=<?php echo $row['id']; ?>&status=approved" class="btn approve">Approve</a>
                <a href="update_booking.php?id=<?php echo $row['id']; ?>&status=rejected" class="btn reject">Reject</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</section>
</body>
</html>