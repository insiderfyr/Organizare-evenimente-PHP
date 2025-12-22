<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Verify if user is admin
if (!has_role('admin')) {
    log_security_event('UNAUTHORIZED_ACCESS', 'Attempted access to admin users page without admin role');
    redirect('/index.php');
}

$success = '';
$error = '';
$current_user_id = get_user_id();

// Process role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $error = "Invalid security token. Please refresh the page and try again.";
        log_security_event('CSRF_VALIDATION_FAILED', 'Role change attempt with invalid CSRF token');
    }
    // Validate request origin
    else if (!validate_request_origin()) {
        $error = "Invalid request origin.";
        log_security_event('INVALID_REQUEST_ORIGIN', 'Role change from suspicious origin');
    }
    else {
        $user_id = sanitize_int($_POST['user_id']);
        $new_role = sanitize($_POST['role']);

        // Check that admin doesn't change their own role
        if ($user_id === $current_user_id) {
            $error = "You cannot change your own role!";
            log_security_event('ROLE_CHANGE_BLOCKED', 'Admin attempted to change own role');
        } else if (!in_array($new_role, ['admin', 'organizer', 'user'])) {
            $error = "Invalid role!";
            log_security_event('INVALID_ROLE', "Invalid role attempted: $new_role");
        } else {
            // Get username for logging
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $target_user = $result->fetch_assoc();
            $stmt->close();
            
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);

            if ($stmt->execute()) {
                $success = "Role changed successfully!";
                log_security_event('ROLE_CHANGED', "User: {$target_user['username']} (ID: $user_id) role changed to: $new_role");
            } else {
                $error = "Error changing role!";
                log_security_event('ROLE_CHANGE_ERROR', "Database error for user ID: $user_id");
            }
            $stmt->close();
        }
    }
}

// Process user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $error = "Invalid security token. Please refresh the page and try again.";
        log_security_event('CSRF_VALIDATION_FAILED', 'User deletion attempt with invalid CSRF token');
    }
    // Validate request origin
    else if (!validate_request_origin()) {
        $error = "Invalid request origin.";
        log_security_event('INVALID_REQUEST_ORIGIN', 'User deletion from suspicious origin');
    }
    else {
        $user_id = sanitize_int($_POST['user_id']);

        // Check that admin doesn't delete themselves
        if ($user_id === $current_user_id) {
            $error = "You cannot delete yourself!";
            log_security_event('USER_DELETE_BLOCKED', 'Admin attempted to delete own account');
        } else {
            // Get username for logging before deletion
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $target_user = $result->fetch_assoc();
            $stmt->close();
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $success = "User deleted successfully!";
                log_security_event('USER_DELETED', "User: {$target_user['username']} (ID: $user_id) deleted by admin");
            } else {
                $error = "Error deleting user!";
                log_security_event('USER_DELETE_ERROR', "Database error for user ID: $user_id");
            }
            $stmt->close();
        }
    }
}

// Get list of users
$users = [];
$result = $conn->query("
    SELECT
        u.id, u.username, u.email, u.role, u.created_at,
        (SELECT COUNT(*) FROM events WHERE organizer_id = u.id) as events_count,
        (SELECT COUNT(*) FROM registrations WHERE user_id = u.id) as registrations_count
    FROM users u
    ORDER BY u.created_at DESC
");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="mb-4">
            <h2 class="mb-2">User management</h2>
            <p class="text-muted">Manage users and roles</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded shadow">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Total users: <?php echo count($users); ?></h5>
                    <a href="/admin/dashboard.php" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Events</th>
                                <th>Registrations</th>
                                <th>Created at</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'organizer' ? 'warning' : 'secondary'); ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['events_count']; ?></td>
                                <td><?php echo $user['registrations_count']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <!-- Change role button -->
                                        <?php if ($user['id'] !== $current_user_id): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal<?php echo $user['id']; ?>">
                                            <i class="bi bi-person-gear"></i> Role
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted small">You (current user)</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Change role modal -->
                                    <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Change role for <?php echo htmlspecialchars($user['username']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <?php echo csrf_token_field(); ?>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Select role</label>
                                                            <select name="role" class="form-control" required>
                                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                                <option value="organizer" <?php echo $user['role'] === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                                            </select>
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-info-circle"></i>
                                                            <strong>Note:</strong> Changing roles affects user permissions immediately.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="change_role" class="btn btn-primary">
                                                            <i class="bi bi-save"></i> Save
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">Confirm deletion</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <?php echo csrf_token_field(); ?>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="alert alert-danger">
                                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                                            <strong>Warning!</strong> This action is irreversible!
                                                        </div>
                                                        <p>Are you sure you want to delete user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                                                        <p class="text-danger">This will permanently delete:</p>
                                                        <ul class="text-danger">
                                                            <li>User account</li>
                                                            <li><?php echo $user['events_count']; ?> events created by this user</li>
                                                            <li><?php echo $user['registrations_count']; ?> event registrations</li>
                                                        </ul>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_user" class="btn btn-danger">
                                                            <i class="bi bi-trash"></i> Yes, Delete User
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
