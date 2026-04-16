<?php
session_start();
include("db.php");

/* ================= SECURITY CHECK ================= */
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* ================= CLEAR ALL BOOKINGS ================= */
if(isset($_POST['clear_all'])){

    // delete messages first (avoid foreign key error)
    $conn->query("DELETE FROM messages");

    // delete bookings
    $conn->query("DELETE FROM bookings");

    $_SESSION['msg'] = "All bookings and messages deleted successfully!";

    header("Location: admin_dashboard.php");
    exit();
}

/* ================= ADMIN INFO ================= */
$admin_name = $_SESSION['admin']['full_name'];

/* ================= STATS ================= */
$total_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];

$pending_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status='Pending'")->fetch_assoc()['total'];

$confirmed_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status='Confirmed'")->fetch_assoc()['total'];

$rejected_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status='Rejected'")->fetch_assoc()['total'];

$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Rewind Studio</title>

<link rel="stylesheet" href="adminv2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
        <span>Admin Panel</span>
    </div>

    <nav>
        <a href="admin_messages.php">Messages <i class="fas fa-comments"></i></a>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_bookings.php">Manage Bookings</a>
        <a href="admin_users.php">Manage Users</a>
        <a href="admin_notifications.php">Notifications <i class="fas fa-bell"></i></a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="hero">

    <h2 style="margin-bottom:20px;">
        Welcome, <?php echo htmlspecialchars($admin_name); ?>
    </h2>

    <!-- MESSAGE -->
    <?php if(isset($_SESSION['msg'])): ?>
        <div style="
            text-align:center;
            margin:10px auto;
            padding:10px;
            width:fit-content;
            background:rgba(255,255,255,0.15);
            border-radius:10px;
            backdrop-filter:blur(10px);
        ">
            <?php 
                echo $_SESSION['msg'];
                unset($_SESSION['msg']);
            ?>
        </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="hero-container" style="display:flex; gap:30px; flex-wrap:wrap;">

        <div class="card" style="padding:30px;border-radius:20px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);flex:1;min-width:200px;text-align:center;">
            <h3>Total Bookings</h3>
            <p><?php echo $total_bookings; ?></p>
        </div>

        <div class="card" style="padding:30px;border-radius:20px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);flex:1;min-width:200px;text-align:center;">
            <h3>Pending</h3>
            <p><?php echo $pending_bookings; ?></p>
        </div>

        <div class="card" style="padding:30px;border-radius:20px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);flex:1;min-width:200px;text-align:center;">
            <h3>Confirmed</h3>
            <p><?php echo $confirmed_bookings; ?></p>
        </div>

        <div class="card" style="padding:30px;border-radius:20px;background:rgba(255,0,0,0.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);flex:1;min-width:200px;text-align:center;">
            <h3>Rejected</h3>
            <p><?php echo $rejected_bookings; ?></p>
        </div>

        <div class="card" style="padding:30px;border-radius:20px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);flex:1;min-width:200px;text-align:center;">
            <h3>Total Users</h3>
            <p><?php echo $total_users; ?></p>
        </div>

    </div>

    <!-- CLEAR BUTTON -->
    <form method="POST"
          onsubmit="return confirm('Are you sure you want to delete ALL bookings? This cannot be undone!');"
          style="text-align:center;margin-top:30px;">
<button type="submit" name="clear_all" class="clear-btn">
    🗑 Clear All Bookings
</button>
        </button>

    </form>

</section>

</body>
</html>