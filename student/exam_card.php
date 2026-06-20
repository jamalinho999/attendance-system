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

if (!$student) {
    die("Student profile incomplete. Please contact admin.");
}

// Get all units for student's course/year/semester
$unitsStmt = $pdo->prepare("
    SELECT * FROM units 
    WHERE course_id = ? AND year = ? AND semester = ?
");
$unitsStmt->execute([$student['course_id'], $student['current_year'], $student['current_semester']]);
$units = $unitsStmt->fetchAll();

// For each unit, calculate attendance %
$unitResults = [];
$allEligible = true;

foreach ($units as $unit) {
    // Total sessions held for this unit
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE unit_id = ?");
    $totalStmt->execute([$unit['unit_id']]);
    $totalSessions = $totalStmt->fetchColumn();

    // Sessions attended by this student
    $attendedStmt = $pdo->prepare("
        SELECT COUNT(*) FROM attendance a
        JOIN sessions s ON a.session_id = s.session_id
        WHERE s.unit_id = ? AND a.student_id = ? AND a.status = 'present'
    ");
    $attendedStmt->execute([$unit['unit_id'], $student_id]);
    $attended = $attendedStmt->fetchColumn();

    $percentage = $totalSessions > 0 ? round(($attended / $totalSessions) * 100, 1) : 0;
    $eligible = $percentage >= 75;

    if (!$eligible) {
        $allEligible = false;
    }

    $unitResults[] = [
        'unit_code' => $unit['unit_code'],
        'unit_name' => $unit['unit_name'],
        'total' => $totalSessions,
        'attended' => $attended,
        'percentage' => $percentage,
        'eligible' => $eligible,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Card - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; background: #fff; color: #000; margin: 0; padding: 40px; }
        .card { max-width: 700px; margin: 0 auto; border: 2px solid #000; padding: 30px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .header h2 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0; font-size: 13px; }
        .info-table { width: 100%; margin-bottom: 20px; font-size: 14px; }
        .info-table td { padding: 4px 0; }
        .info-table td:first-child { font-weight: bold; width: 180px; }
        table.units { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        table.units th, table.units td { border: 1px solid #000; padding: 8px; text-align: left; }
        table.units th { background: #eee; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .overall { margin-top: 20px; padding: 12px; text-align: center; font-weight: bold; font-size: 15px; border: 2px solid #000; }
        .overall.eligible { background: #e6ffe6; }
        .overall.not-eligible { background: #ffe6e6; }
        .print-btn { display: block; margin: 20px auto; text-align: center; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2>KABARAK UNIVERSITY</h2>
            <p>Examination Card</p>
            <p><?php echo htmlspecialchars($student['course_name']); ?> &mdash; Year <?php echo $student['current_year']; ?>, Semester <?php echo $student['current_semester']; ?></p>
        </div>

        <table class="info-table">
            <tr><td>Student Name:</td><td><?php echo htmlspecialchars($student['full_name']); ?></td></tr>
            <tr><td>University ID:</td><td><?php echo htmlspecialchars($student['university_id']); ?></td></tr>
            <tr><td>Course:</td><td><?php echo htmlspecialchars($student['course_name']); ?> (<?php echo htmlspecialchars($student['course_code']); ?>)</td></tr>
            <tr><td>Academic Period:</td><td>Year <?php echo $student['current_year']; ?>, Semester <?php echo $student['current_semester']; ?></td></tr>
            <tr><td>Date Issued:</td><td><?php echo date('d F Y'); ?></td></tr>
        </table>

        <table class="units">
            <thead>
                <tr><th>Unit Code</th><th>Unit Name</th><th>Sessions Held</th><th>Attended</th><th>Attendance %</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($unitResults as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['unit_code']); ?></td>
                    <td><?php echo htmlspecialchars($r['unit_name']); ?></td>
                    <td><?php echo $r['total']; ?></td>
                    <td><?php echo $r['attended']; ?></td>
                    <td><?php echo $r['percentage']; ?>%</td>
                    <td class="<?php echo $r['eligible'] ? 'pass' : 'fail'; ?>">
                        <?php echo $r['eligible'] ? 'PASS' : 'FAIL'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="overall <?php echo $allEligible ? 'eligible' : 'not-eligible'; ?>">
            <?php if ($allEligible): ?>
                ✓ ELIGIBLE FOR ALL EXAMINATIONS THIS SEMESTER
            <?php else: ?>
                ✗ NOT ELIGIBLE — One or more units below 75% attendance threshold
            <?php endif; ?>
        </div>
    </div>

    <div class="print-btn">
        <button onclick="window.print()" style="padding: 10px 24px; font-size: 14px; cursor: pointer;">Print / Save as PDF</button>
    </div>
</body>
</html>