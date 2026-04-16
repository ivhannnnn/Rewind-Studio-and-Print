<?php
session_start();
include("db.php");

if(isset($_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['password'])){
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR username=?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $_SESSION['signup_error'] = "Email or username already exists!";
        header("Location: login.php");
        exit();
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO users (username, full_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $username, $full_name, $email, $password);
        if($stmt_insert->execute()){
            // ✅ Set signup success message
            $_SESSION['signup_success'] = "Account created successfully! You can now log in.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['signup_error'] = "Something went wrong. Please try again!";
            header("Location: login.php");
            exit();
        }
    }
} else {
    $_SESSION['signup_error'] = "Please fill in all fields!";
    header("Location: login.php");
    exit();
}
?>