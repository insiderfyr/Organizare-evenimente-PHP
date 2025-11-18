<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Verific dac user-ul este admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('/index.php');
}

$success = '';
$error = '';
$current_user_id = get_user_id();

// Procesare schimbare rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = sanitize($_POST['role']);

    // Verific ca adminul s nu îi schimbe propriul rol
    if ($user_id === $current_user_id) {
        $error = "Nu îi poi schimba propriul rol!";
    } else if (!in_array($new_role, ['admin', 'organizer', 'user'])) {
        $error = "Rol invalid!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);

        if ($stmt->execute()) {
            $success = "Rol schimbat cu succes!";
        } else {
            $error = "Eroare la schimbarea rolului!";
        }
        $stmt->close();
    }
}

// Procesare tergere utilizator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];

    // Verific ca adminul s nu se tearg pe sine
    if ($user_id === $current_user_id) {
        $error = "Nu te poi terge pe tine însui!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $success = "Utilizator ters cu succes!";
        } else {
            $error = "Eroare la tergerea utilizatorului!";
        }
        $stmt->close();
    }
}

// Preia lista de utilizatori
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
            <h2 class="mb-2">Gestionare utilizatori</h2>
            <p class="text-muted">Administrare utilizatori i roluri</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded shadow">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Total utilizatori: <?php echo count($users); ?></h5>
                    <a href="/admin/dashboard.php" class="btn btn-sm btn-secondary">Înapoi la Dashboard</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Evenimente</th>
                                <th>Înregistrri</th>
                                <th>Creat la</th>
                                <th>Aciuni</th>
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
                                        <!-- Buton schimbare rol -->
                                        <?php if ($user['id'] !== $current_user_id): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal<?php echo $user['id']; ?>">
                                            Rol
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                            terge
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted small">Tu</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Modal schimbare rol -->
                                    <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Schimb rol pentru <?php echo htmlspecialchars($user['username']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Selecteaz rolul</label>
                                                            <select name="role" class="form-control" required>
                                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Utilizator</option>
                                                                <option value="organizer" <?php echo $user['role'] === 'organizer' ? 'selected' : ''; ?>>Organizator</option>
                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuleaz</button>
                                                        <button type="submit" name="change_role" class="btn btn-primary">Salveaz</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal tergere -->
                                    <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmare tergere</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <p>Eti sigur c vrei s tergi utilizatorul <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                                                        <p class="text-danger">Aceast aciune va terge toate evenimentele i înregistrrile asociate!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuleaz</button>
                                                        <button type="submit" name="delete_user" class="btn btn-danger">terge</button>
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
