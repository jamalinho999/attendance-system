<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unit'])) {
    $unit_code = trim($_POST['unit_code']);
    $unit_name = trim($_POST['unit_name']);
    $course_id = $_POST['course_id'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $lecturer_id = $_POST['lecturer_id'];

    if (empty($unit_code) || empty($unit_name)) {
        $error = "Unit code and name are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO units (lecturer_id, unit_code, unit_name, course_id, year, semester) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$lecturer_id, $unit_code, $unit_name, $course_id, $year, $semester]);
        $success = "Unit added successfully.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_lecturer'])) {
    $unit_id = $_POST['unit_id'];
    $new_lecturer_id = $_POST['new_lecturer_id'];
    $stmt = $pdo->prepare("UPDATE units SET lecturer_id = ? WHERE unit_id = ?");
    $stmt->execute([$new_lecturer_id, $unit_id]);
    $success = "Lecturer reassigned successfully.";
}
if (isset($_GET['delete'])) {
    $unit_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM units WHERE unit_id = ?");
    $stmt->execute([$unit_id]);
    $success = "Unit removed.";
}


$courses = $pdo->query("SELECT * FROM courses")->fetchAll();


$lecturers = $pdo->query("
    SELECT u.user_id, u.full_name FROM users u 
    JOIN roles r ON u.role_id = r.role_id 
    WHERE r.role_name = 'lecturer'
")->fetchAll();

$units = $pdo->query("
    SELECT units.*, courses.course_name, users.full_name AS lecturer_name
    FROM units
    JOIN courses ON units.course_id = courses.course_id
    JOIN users ON units.lecturer_id = users.user_id
    ORDER BY units.course_id, units.year, units.semester
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Units - AttendEase</title>
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
        .form-control, .form-select { background-color: #0d1117; color: #e6edf3; border: 1px solid #30363d; }
        .form-control:focus, .form-select:focus { background-color: #0d1117; color: #e6edf3; border-color: #58a6ff; box-shadow: none; }
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
            <h4 class="mb-4">Manage Units</h4>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <div class="stat-card p-3 mb-4">
                <h6 class="mb-3">Add new unit</h6>
                <form method="POST" class="row g-2">
                    <div class="col-md-2"><label>Unit Code</label><input type="text" name="unit_code" class="form-control" placeholder="ICT 121" required></div>
                    <div class="col-md-3"><label>Unit Name</label><input type="text" name="unit_name" class="form-control" placeholder="Intro to Programming" required></div>
                    <div class="col-md-2"><label>Course</label>
                        <select name="course_id" class="form-select" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_code']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1"><label>Year</label>
                        <select name="year" class="form-select">
                            <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option>
                        </select>
                    </div>
                    <div class="col-md-1"><label>Sem</label>
                        <select name="semester" class="form-select">
                            <option value="1">1</option><option value="2">2</option>
                        </select>
                    </div>
                    <div class="col-md-2"><label>Lecturer</label>
                        <select name="lecturer_id" class="form-select" required>
                            <?php foreach ($lecturers as $l): ?>
                                <option value="<?php echo $l['user_id']; ?>"><?php echo htmlspecialchars($l['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" name="add_unit" class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
            </div>

            <div class="stat-card p-3">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Code</th><th>Name</th><th>Course</th><th>Year/Sem</th><th>Lecturer</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['unit_code']); ?></td>
                            <td><?php echo htmlspecialchars($u['unit_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['course_name']); ?></td>
                            <td>Y<?php echo $u['year']; ?> S<?php echo $u['semester']; ?></td>
                            <td>
    <form method="POST" class="d-flex gap-1">
        <input type="hidden" name="unit_id" value="<?php echo $u['unit_id']; ?>">
        <select name="new_lecturer_id" class="form-select form-select-sm">
            <?php foreach ($lecturers as $l): ?>
                <option value="<?php echo $l['user_id']; ?>" <?php echo ($l['user_id'] == $u['lecturer_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($l['full_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="reassign_lecturer" class="btn btn-sm btn-outline-info">Save</button>
    </form>
</td>
                            <td>
                                <a href="?delete=<?php echo $u['unit_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this unit?')">Remove</a>
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