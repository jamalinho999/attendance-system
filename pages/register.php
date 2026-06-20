<?php
session_start();
require_once '../includes/db.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $university_id = trim($_POST['university_id']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_name = $_POST['role']; // admin, lecturer, student

    if (empty($full_name) || empty($university_id) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Get role_id from role name
        $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $stmt->execute([$role_name]);
        $role = $stmt->fetch();

        if (!$role) {
            $error = "Invalid role selected.";
        } else {
            // Check if university_id or email already exists
            $check = $pdo->prepare("SELECT * FROM users WHERE university_id = ? OR email = ?");
            $check->execute([$university_id, $email]);

            if ($check->rowCount() > 0) {
                $error = "University ID or email already registered.";
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                $insert = $pdo->prepare("INSERT INTO users (role_id, full_name, university_id, email, password_hash) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$role['role_id'], $full_name, $university_id, $email, $password_hash]);

                $success = "Registration successful! You can now log in.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - AttendEase</title>
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
    <div class="card p-4" style="width: 400px;">
        <h3 class="text-center mb-3" style="color:#58a6ff;">AttendEase</h3>
        <p class="text-center text-secondary mb-4">Create an account</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>University ID</label>
                <input type="text" name="university_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Role</label>
                <select name="role" class="form-control" required>
                    <option value="student">Student</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        <p class="text-center mt-3 text-secondary">Already have an account? <a href="login.php" style="color:#58a6ff;">Login</a></p>
    </div>
</body>
</html>