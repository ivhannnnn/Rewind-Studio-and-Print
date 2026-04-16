<?php
session_start();
include("db.php");

/* ================= SECURITY HEADERS ================= */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

/* ================= SESSION CHECK ================= */
if(!isset($_SESSION['admin']) || empty($_SESSION['admin'])){
    header("Location: admin_login.php"); 
    exit();
}

/* ================= CSRF TOKEN ================= */
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ================= HANDLE ACTION ================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    // Validate CSRF
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        die("Invalid CSRF token");
    }

    // Validate ID
    if(!isset($_POST['id']) || !is_numeric($_POST['id'])){
        die("Invalid ID");
    }

    $id = (int) $_POST['id'];

    // Validate action
    if(!isset($_POST['action']) || !in_array($_POST['action'], ['approve','reject'])){
        die("Invalid action");
    }

    $action = $_POST['action'] === 'approve' ? 'Confirmed' : 'Rejected';

    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->bind_param("si",$action,$id);
    $stmt->execute();

    header("Location: admin_bookings.php");
    exit();
}

/* ================= FETCH BOOKINGS ================= */
$bookings = $conn->query("
    SELECT b.*, u.full_name, u.email 
    FROM bookings b 
    JOIN users u ON b.user_id=u.id
    ORDER BY b.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Bookings - Admin</title>
<link rel="stylesheet" href="admin.css">

<style>
.booking-container {
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 10px;
}

.booking-card {
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 10px;
    background: rgba(255,255,255,0.1);
}

.booking-card p {
    margin: 5px 0;
}

.booking-card .btn {
    display: inline-block;
    margin-right: 5px;
    padding: 5px 10px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    color: #fff;
}

.booking-card .approve { background-color: #28a745; }
.booking-card .reject { background-color: #dc3545; }
</style>
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
    <h1>Bookings</h1>

    <?php if($bookings->num_rows > 0): ?>
        <?php while($row=$bookings->fetch_assoc()): ?>
            <div class="booking-card">

                <p><strong>ID:</strong> <?php echo $row['id']; ?></p>

                <p><strong>User:</strong> 
                    <?php echo htmlspecialchars($row['full_name']); ?> 
                    (<?php echo htmlspecialchars($row['email']); ?>)
                </p>

                <p><strong>Service:</strong> 
                    <?php echo htmlspecialchars($row['service_name']); ?>
                </p>

                <p><strong>Date:</strong> <?php echo $row['created_at']; ?></p>

                <p><strong>Status:</strong> 
                    <?php echo htmlspecialchars($row['status']); ?>
                </p>

                <?php if($row['status']=='Pending'): ?>
                    
                    <!-- APPROVE -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn approve">Approve</button>
                    </form>

<a href="admin_chat.php?user_id=<?php echo $row['user_id']; ?>&booking_id=<?php echo $row['id']; ?>" 
   class="btn message">
   💬 Message User
</a>
                    <!-- REJECT -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn reject">Reject</button>
                    </form>

                <?php endif; ?>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No bookings yet.</p>
    <?php endif; ?>

</div>
</section>

</body>
</html>