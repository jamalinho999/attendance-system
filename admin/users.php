<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

$error = "";
$success = "";

// Handle Remove User
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    // Prevent admin from deleting themselves
    if ($delete_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        // Remove attendance records first
        $pdo->prepare("DELETE FROM attendance WHERE student_id = ?")->execute([$delete_id]);
        // Remove enrollments
        $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?")->execute([$delete_id]);
        // Remove user
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$delete_id]);
        $success = "User removed successfully.";
    }
}

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role_id = $_POST['role_id'];
    $pdo->prepare("UPDATE users SET role_id = ? WHERE user_id = ?")->execute([$new_role_id, $user_id]);
    $success = "Role updated successfully.";
}

// Get all users with role name
$users = $pdo->query("
    SELECT users.*, roles.role_name 
    FROM users 
    JOIN roles ON users.role_id = roles.role_id
    ORDER BY roles.role_name, users.full_name
")->fetchAll();

// Get all roles for dropdown
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - AttendEase</title>
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
        .form-select { background-color: #0d1117; color: #e6edf3; border: 1px solid #30363d; }
        .form-select:focus { background-color: #0d1117; color: #e6edf3; border-color: #58a6ff; box-shadow: none; }
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
            <h4 class="mb-4">Manage Users</h4>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <div class="stat-card p-3">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr><th>Name</th><th>University ID</th><th>Email</th><th>Role</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['university_id']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                    <select name="role_id" class="form-select form-select-sm">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?php echo $r['role_id']; ?>" <?php echo ($r['role_id'] == $u['role_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($r['role_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="change_role" class="btn btn-sm btn-outline-info">Save</button>
                                </form>
                            </td>
                            <td>
                                <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this user?')">Remove</a>
                                <?php else: ?>
                                <span class="text-secondary small">You</span>
                                <?php endif; ?>
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