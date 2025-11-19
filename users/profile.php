<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

$user_id = get_user_id();

// Preia informații user
$stmt = $conn->prepare("SELECT username, email, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Dacă user-ul e organizer/admin, preia evenimentele create
$my_events = [];
if ($user['role'] === 'admin' || $user['role'] === 'organizer') {
    $stmt = $conn->prepare("SELECT id, title, date, location, max_participants, created_at FROM events WHERE organizer_id = ? ORDER BY date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $my_events[] = $row;
    }
    $stmt->close();
}

// Preia înregistrările utilizatorului
$my_registrations = [];
$stmt = $conn->prepare("
    SELECT e.id, e.title, e.date, e.location, r.registration_date
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    WHERE r.user_id = ?
    ORDER BY e.date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $my_registrations[] = $row;
}
$stmt->close();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="bg-white p-4 rounded shadow mb-4">
                    <h4 class="mb-3">Informații profil</h4>
                    <div class="mb-3">
                        <strong>Username:</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div class="mb-3">
                        <strong>Email:</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="mb-3">
                        <strong>Rol:</strong>
                        <p class="mb-0">
                            <?php
                            $role_labels = [
                                'admin' => 'Administrator',
                                'organizer' => 'Organizator',
                                'user' => 'Utilizator'
                            ];
                            echo htmlspecialchars($role_labels[$user['role']] ?? $user['role']);
                            ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <strong>Membru din:</strong>
                        <p class="mb-0"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Evenimente create (doar pentru organizer/admin) -->
                <?php if ($user['role'] === 'admin' || $user['role'] === 'organizer'): ?>
                <div class="bg-white p-4 rounded shadow mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Evenimentele mele</h4>
                        <a href="/events/create_event.php" class="btn btn-primary btn-sm">Creează eveniment</a>
                    </div>

                    <?php if (empty($my_events)): ?>
                        <p class="text-muted">Nu ai creat încă niciun eveniment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Titlu</th>
                                        <th>Data</th>
                                        <th>Locație</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($event['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td>
                                            <a href="/events/view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info">Detalii</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Înregistrări la evenimente -->
                <div class="bg-white p-4 rounded shadow">
                    <h4 class="mb-3">Înregistrările mele</h4>

                    <?php if (empty($my_registrations)): ?>
                        <p class="text-muted">Nu ești înscris la niciun eveniment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Eveniment</th>
                                        <th>Data</th>
                                        <th>Locație</th>
                                        <th>Înscris la</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_registrations as $reg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reg['title']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($reg['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($reg['location']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($reg['registration_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
