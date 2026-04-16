<?php
session_start();
include("db.php");

// ✅ Correct admin session check
if(!isset($_SESSION['admin']) || empty($_SESSION['admin'])){
    header("Location: admin_login.php"); // redirect to admin login page
    exit();
}

// Delete user
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: admin_users.php");
    exit();
}

// Fetch non-admin users
$users = $conn->query("SELECT * FROM users WHERE is_admin=0 ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users - Admin</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
    <div class="logo">Admin Panel</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_bookings.php">Manage Bookings</a>
        <a href="admin_users.php">Manage Users</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="hero">
    <div class="booking-container">
        <h1>Users</h1>
        <?php while($user=$users->fetch_assoc()): ?>
            <div class="booking-card">
                <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                <p><strong>Full Name:</strong> <?php echo $user['full_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                <a href="?delete=<?php echo $user['id']; ?>" class="btn reject">Delete</a>
            </div>
        <?php endwhile; ?>
    </div>
</section>
</body>
</html>