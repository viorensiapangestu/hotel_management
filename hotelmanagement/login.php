<?php
// login.php
session_start();
require_once 'db.php';
require_once 'functions.php';

// If the user is already logged in, redirect them to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // Passwords shouldn't be heavily sanitized before verification to preserve special characters

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Fetch user from database
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify user exists and password matches
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect to dashboard
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Grand Hotel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #1e293b;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <i class="fa-solid fa-hotel fa-3x text-primary mb-3"></i>
        <h3 class="fw-bold text-dark">Grand Hotel</h3>
        <p class="text-muted">Staff / Admin Portal</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 text-center">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="email" class="form-label text-muted fw-semibold">Email Address</label>
            <input type="email" class="form-control form-control-lg" id="email" name="email" required autofocus>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label text-muted fw-semibold">Password</label>
            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Login</button>
    </form>
</div>

</body>
</html>