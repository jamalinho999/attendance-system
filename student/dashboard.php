<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../pages/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student info + course
$stmt = $pdo->prepare("
    SELECT users.*, courses.course_name, courses.course_code
    FROM users
    JOIN courses ON users.course_id = courses.course_id
    WHERE users.user_id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Get all units for student's course/year/semester
$unitsStmt = $pdo->prepare("
    SELECT * FROM units 
    WHERE course_id = ? AND year = ? AND semester = ?
");
$unitsStmt->execute([$student['course_id'], $student['current_year'], $student['current_semester']]);
$units = $unitsStmt->fetchAll();

// Calculate attendance % per unit
$unitResults = [];
foreach ($units as $unit) {
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE unit_id = ?");
    $totalStmt->execute([$unit['unit_id']]);
    $totalSessions = $totalStmt->fetchColumn();

    $attendedStmt = $pdo->prepare("
        SELECT COUNT(*) FROM attendance a
        JOIN sessions s ON a.session_id = s.session_id
        WHERE s.unit_id = ? AND a.student_id = ? AND a.status = 'present'
    ");
    $attendedStmt->execute([$unit['unit_id'], $student_id]);
    $attended = $attendedStmt->fetchColumn();

    $percentage = $totalSessions > 0 ? round(($attended / $totalSessions) * 100, 1) : 0;
    $unitResults[] = [
        'unit_code' => $unit['unit_code'],
        'unit_name' => $unit['unit_name'],
        'total' => $totalSessions,
        'attended' => $attended,
        'percentage' => $percentage,
        'alert' => $percentage < 75 && $totalSessions > 0,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - AttendEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #e6edf3; }
        .navbar { background-color: #161b22; border-bottom: 1px solid #2a2d35; }
        .sidebar { background-color: #0d1117; border-right: 1px solid #2a2d35; min-height: calc(100vh - 56px); }
        .nav-link { color: #8b949e; }
        .nav-link.active { color: #7ee787 !important; background-color: #161b22; }
        .unit-card { background-color: #161b22; border: 1px solid #2a2d35; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .progress { background-color: #2a2d35; height: 6px; border-radius: 3px; }
        .alert-card { border-left: 3px solid #e3b341 !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand px-3">
        <span class="navbar-brand" style="color:#58a6ff;">AttendEase</span>
        <div class="ms-auto d-flex align-items-center">
            <span class="badge me-3" style="background-color:#1a3a2a; color:#7ee787;">Student</span>
            <span class="text-secondary me-3"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../pages/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </nav>
    <div class="d-flex">
        <div class="sidebar p-3" style="width: 200px;">
            <a href="dashboard.php" class="nav-link active rounded p-2 mb-1">Dashboard</a>
            <a href="mark_attendance.php" class="nav-link rounded p-2 mb-1">Mark Attendance</a>
            <a href="exam_card.php" class="nav-link rounded p-2 mb-1">Exam Card</a>
        </div>
        <div class="flex-grow-1 p-4">
            <h4 class="mb-1">My Attendance</h4>
            <p class="text-secondary mb-4">
                <?php echo htmlspecialchars($student['course_name']); ?> — 
                Year <?php echo $student['current_year']; ?>, 
                Semester <?php echo $student['current_semester']; ?>
            </p>

            <?php foreach ($unitResults as $r): ?>
            <div class="unit-card <?php echo $r['alert'] ? 'alert-card' : ''; ?>">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="fw-medium"><?php echo htmlspecialchars($r['unit_name']); ?></span>
                        <span class="text-secondary small ms-2"><?php echo htmlspecialchars($r['unit_code']); ?></span>
                    </div>
                    <span class="fw-bold" style="color: <?php echo $r['percentage'] >= 75 ? '#7ee787' : '#e3b341'; ?>">
                        <?php echo $r['percentage']; ?>%
                    </span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar" style="width: <?php echo $r['percentage']; ?>%; background-color: <?php echo $r['percentage'] >= 75 ? '#238636' : '#9e6a03'; ?>;"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-secondary small"><?php echo $r['attended']; ?> of <?php echo $r['total']; ?> sessions attended</span>
                    <?php if ($r['alert']): ?>
                        <span style="color:#e3b341; font-size:0.8rem;">⚠ Below 75% threshold</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($unitResults)): ?>
                <p class="text-secondary">No units found for your current course, year and semester.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>