<?php
session_start();
include("db.php");

/* ================= SECURITY CHECK ================= */
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* ================= FETCH MESSAGES ================= */
$messages = $conn->query("
    SELECT m.*, u.full_name, u.email
    FROM messages m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messages - Rewind Admin</title>

<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <img src="385319258_714193217393851_8500146797645932462_n (1).jpg">
            <h2>REWIND<br><span>ADMIN</span></h2>
        </div>

        <nav>
            <a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="admin_bookings.php"><i class="fas fa-calendar"></i> Bookings</a>
            
            <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            
            <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <div class="topbar">
            <h1>User Messages</h1>
        </div>

        <div class="message-grid">

            <?php if($messages->num_rows > 0): ?>
                <?php while($msg = $messages->fetch_assoc()): ?>

                    <div class="message-card glass">

                        <div class="message-header">
                            <h3><?php echo htmlspecialchars($msg['full_name']); ?></h3>
                            <span class="email"><?php echo htmlspecialchars($msg['email']); ?></span>
                        </div>

                        <div class="message-body">
                            <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        </div>

                        <div class="message-footer">
                            <span class="time">
                                <i class="fas fa-clock"></i>
                                <?php echo $msg['created_at']; ?>
                            </span>

                            <span class="status <?php echo strtolower($msg['status']); ?>">
                                <?php echo $msg['status']; ?>
                            </span>
                        </div>

                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty">No messages found.</p>
            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>