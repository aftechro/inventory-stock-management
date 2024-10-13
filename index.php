<?php
session_start();
require 'db.php'; // Make sure this path is correct

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = ''; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve user input
    $username = sanitize($_POST['username'], $conn);
    $password = sanitize($_POST['password'], $conn);

    // Debug: Output the sanitized inputs
    // echo "Username: $username, Password: $password"; // Uncomment for debugging

    // Get user data from the database
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    // Debug: Check if the SQL query was successful
    if (!$result) {
        die("Database query failed: " . $conn->error);
    }

    $user = $result->fetch_assoc();

    // Debug: Check if user exists
    // var_dump($user); // Uncomment for debugging

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables for login
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_id'] = $user['id']; // Store user ID in session
        $_SESSION['user_logged_in'] = true; // Mark user as logged in

        // Redirect to the URL stored in session (if any), or dashboard
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect_url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']); // Clear redirect after using it
            header("Location: $redirect_url");
        } else {
            header("Location: dashboard.php"); // Default redirect
        }
        exit;
    } else {
        $error = "Invalid login credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'header.php'; ?>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full height to center vertically */
            margin: 0;
        }
        .login-container {
            width: 100%;
            max-width: 400px; /* Maximum width for larger screens */
            padding: 30px;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-align: center; /* Center text in the container */
            margin: 20px; /* Added margin for small screens */
        }
        h2 {
            margin-bottom: 20px;
            color: #007bff;
        }
        .alert {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: bold;
        }
        .form-control {
            height: 60px; /* Increased height for input fields */
            font-size: 20px; /* Increased font size for better readability */
            padding: 10px; /* Added padding for comfort */
        }
        .btn-primary {
            height: 60px; /* Increased height for button */
            font-size: 20px; /* Increased font size for button */
            width: 100%; /* Full width */
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .login-container {
                width: 90%; /* Full width on smaller screens */
                padding: 20px; /* Reduced padding for smaller screens */
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><i class="fas fa-user-lock"></i> Login</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
                
    <?php require 'footer.php'; ?>

