<?php
session_start();
require_once '../includes/db.php';
$courses = $pdo->query("SELECT * FROM courses")->fetchAll();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $university_id = trim($_POST['university_id']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_name = $_POST['role']; // admin, lecturer, student
$course_id = $_POST['course_id'] ?? null;
$current_year = $_POST['current_year'] ?? null;
$current_semester = $_POST['current_semester'] ?? null;

// Only students need course/year/semester
if ($role_name !== 'student') {
    $course_id = null;
    $current_year = null;
    $current_semester = null;
}
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

               $insert = $pdo->prepare("INSERT INTO users (role_id, full_name, university_id, email, password_hash, course_id, current_year, current_semester) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$insert->execute([$role['role_id'], $full_name, $university_id, $email, $password_hash, $course_id, $current_year, $current_semester]);

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
        
            <div class="mb-3">
    <label>Role</label>
    <select name="role" id="roleSelect" class="form-control" required onchange="toggleStudentFields()">
        <option value="student">Student</option>
        <option value="lecturer">Lecturer</option>
        <option value="admin">Admin</option>
    </select>
</div>
<div id="studentFields">
    <div class="mb-3">
        <label>Course</label>
        <select name="course_id" class="form-control">
            <?php foreach ($courses as $c): ?>
                <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col-6 mb-3">
            <label>Current Year</label>
            <select name="current_year" class="form-control">
                <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option>
            </select>
        </div>
        <div class="col-6 mb-3">
            <label>Current Semester</label>
            <select name="current_semester" class="form-control">
                <option value="1">1</option><option value="2">2</option>
            </select>
        </div>
    </div>
</div>
<script>
function toggleStudentFields() {
    document.getElementById('studentFields').style.display = 
        document.getElementById('roleSelect').value === 'student' ? 'block' : 'none';
}
</script>
            
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        <p class="text-center mt-3 text-secondary">Already have an account? <a href="login.php" style="color:#58a6ff;">Login</a></p>
    </div>
</body>
</html>