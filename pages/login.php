<?php
session_start();
require_once '../includes/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $university_id = trim($_POST['university_id']);
    $password = $_POST['password'];

    if (empty($university_id) || empty($password)) {
        $error = "Please enter both University ID and password.";
    } else {
        $stmt = $pdo->prepare("
            SELECT users.*, roles.role_name 
            FROM users 
            JOIN roles ON users.role_id = roles.role_id 
            WHERE university_id = ?
        ");
        $stmt->execute([$university_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful — set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];

            // Redirect based on role
            if ($user['role_name'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role_name'] === 'lecturer') {
                header("Location: ../lecturer/dashboard.php");
            } else {
                header("Location: ../student/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid University ID or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - AttendEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #e6edf3; }
        .card { background-color: #161b22; border: 1px solid #2a2d35; }
        .form-control { background-color: #0d1117; color: #e6edf3; border: 1px solid #30363d; }
        .form-control:focus { background-color: #0d1117; color: #e6edf3; border-color: #58a6ff; box-shadow: none; }
        label { color: #8b949e; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card p-4" style="width: 380px;">
        <h3 class="text-center mb-1" style="color:#58a6ff;">AttendEase</h3>
        <p class="text-center text-secondary mb-4">University Attendance System</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>University ID</label>
                <input type="text" name="university_id" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
        <p class="text-center mt-3 text-secondary">Don't have an account? <a href="register.php" style="color:#58a6ff;">Register</a></p>
    </div>
</body>
</html>