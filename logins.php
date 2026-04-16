<?php
session_start();
include("db.php");

if (!empty($_POST['username']) && !empty($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = [
                'id' => $row['id'],
                'full_name' => $row['full_name'],
                'email' => $row['email']
            ];
            // ✅ Set login success message
            $_SESSION['login_success'] = "Welcome back, " . $row['full_name'] . "!";
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid password!";
        }
    } else {
        $_SESSION['login_error'] = "User not found!";
    }
    header("Location: login.php");
    exit();
}

// ===== GOOGLE LOGIN =====
if (!empty($_POST['google_id']) && !empty($_POST['email'])) {
    $google_id = $_POST['google_id'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'] ?? "Google User";

    $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
    $stmt->bind_param("ss", $google_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
       $_SESSION['user'] = [
    'id' => $row['id'],
    'full_name' => $row['full_name'],
    'email' => $row['email']
];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO users (full_name, email, google_id) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $full_name, $email, $google_id);
        $stmt_insert->execute();
        $_SESSION['user'] = $full_name;
    }
    header("Location: dashboard.php");
    exit();
}

// ===== FACEBOOK LOGIN =====
if (!empty($_POST['facebook_id']) && !empty($_POST['email'])) {
    $facebook_id = $_POST['facebook_id'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'] ?? "Facebook User";

    $stmt = $conn->prepare("SELECT * FROM users WHERE facebook_id = ? OR email = ?");
    $stmt->bind_param("ss", $facebook_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
       $new_id = $stmt_insert->insert_id;

$_SESSION['user'] = [
    'id' => $new_id,
    'full_name' => $full_name,
    'email' => $email
];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO users (full_name, email, facebook_id) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $full_name, $email, $facebook_id);
        $stmt_insert->execute();
        $_SESSION['user'] = $full_name;
    }
    header("Location: dashboard.php");
    exit();
}
?>
