<?php
session_start();
include("db.php");

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

if(isset($_POST['message_id'], $_POST['reply_text'])){
    $message_id = intval($_POST['message_id']);
    $reply_text = trim($_POST['reply_text']);
    $admin_id = $_SESSION['admin']['id'];

    if($reply_text !== ""){
        // Insert reply into admin_replies table
        $stmt = $conn->prepare("INSERT INTO admin_replies (message_id, admin_id, reply_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $message_id, $admin_id, $reply_text);
        $stmt->execute();

        // Optionally mark message as "Read"
        $conn->query("UPDATE messages SET status='Read' WHERE id={$message_id}");
    }
}

header("Location: admin_notifications.php");
exit();