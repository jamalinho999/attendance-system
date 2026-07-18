<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

$error = "";
$success = "";

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);

    if (empty($course_name) || empty($course_code)) {
        $error = "Course name and code are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_code) VALUES (?, ?)");
            $stmt->execute([$course_name, $course_code]);
            $success = "Course added successfully.";
        } catch (PDOException $e) {
            $error = "Course code already exists.";
        }
    }
}

// Handle Remove Course
if (isset($_GET['delete'])) {
    $course_id = $_GET['delete'];
    // Check if any units depend on this course first
    $check = $pdo->prepare("SELECT COUNT(*) FROM units WHERE course_id = ?");
    $check->execute([$course_id]);
    if ($check->fetchColumn() > 0) {
        $error = "Cannot remove course — it still has units assigned to it. Remove those units first.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $success = "Course removed.";
    }
}

$courses = $pdo->query("
    SELECT courses.*, COUNT(units.unit_id) AS unit_count 
    FROM courses 
    LEFT JOIN units ON courses.course_id = units.course_id 
    GROUP BY courses.course_id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses - AttendEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #e6edf3; }
        .navbar { background-color: #161b22; border-bottom: 1px solid #2a2d35; }
        .sidebar { background-color: #0d1117; border-right: 1px solid #2a2d35; min-height: calc(100vh - 56px); }
        .nav-link { color: #8b949e; }
        .nav-link.active { color: #58a6ff !important; background-color: #161b22; }
        .stat-card { background-color: #161b22; border: 1px solid #2a2d35; border-radius: 8px; }
        .table { color: #c9d1d9; }
        .table th { color: #8b949e; border-color: #2a2d35; }
        .table td { border-color: #1a1f27; vertical-align: middle; }
        .form-control { background-color: #0d1117; color: #e6edf3; border: 1px solid #30363d; }
        .form-control:focus { background-color: #0d1117; color: #e6edf3; border-color: #58a6ff; box-shadow: none; }
        label { color: #8b949e; font-size: 0.85rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand px-3">
        <span class="navbar-brand" style="color:#58a6ff;">AttendEase</span>
        <div class="ms-auto d-flex align-items-center">
            <span class="badge bg-primary me-3">Admin</span>
            <span class="text-secondary me-3"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../pages/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div class="flex-grow-1 p-4">
            <h4 class="mb-4">Manage Courses</h4>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <div class="stat-card p-3 mb-4">
                <h6 class="mb-3">Add new course</h6>
                <form method="POST" class="row g-2">
                    <div class="col-md-6"><label>Course Name</label><input type="text" name="course_name" class="form-control" placeholder="Bachelor of Science in Information Technology" required></div>
                    <div class="col-md-3"><label>Course Code</label><input type="text" name="course_code" class="form-control" placeholder="BSCIT" required></div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" name="add_course" class="btn btn-primary w-100">Add Course</button>
                    </div>
                </form>
            </div>

            <div class="stat-card p-3">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Code</th><th>Course Name</th><th>Units</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($c['course_name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $c['unit_count']; ?></span></td>
                            <td>
                                <a href="?delete=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this course?')">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>