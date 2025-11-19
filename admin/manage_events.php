<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Verific dac user-ul este admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('/index.php');
}

$success = '';
$error = '';

// Procesare tergere eveniment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $event_id = (int)$_POST['event_id'];

    // terge mai �nt�i toate �nregistrrile
    $stmt = $conn->prepare("DELETE FROM registrations WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();

    // Apoi terge evenimentul
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);

    if ($stmt->execute()) {
        $success = "Eveniment ters cu succes!";
    } else {
        $error = "Eroare la tergerea evenimentului!";
    }
    $stmt->close();
}

// Filtre
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search_filter = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Construire query
$query = "
    SELECT
        e.id, e.title, e.date, e.location, e.category, e.max_participants,
        u.username as organizer_name,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as registrations_count
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    WHERE 1=1
";

$params = [];
$types = '';

// Aplicare filtre
if (!empty($search_filter)) {
    $query .= " AND (e.title LIKE ? OR e.location LIKE ?)";
    $search_param = '%' . $search_filter . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($status_filter === 'upcoming') {
    $query .= " AND e.date >= NOW()";
} else if ($status_filter === 'past') {
    $query .= " AND e.date < NOW()";
}

$query .= " ORDER BY e.date DESC";

// Execuie query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="mb-4">
            <h2 class="mb-2">Gestionare evenimente</h2>
            <p class="text-muted">Administrare evenimente din sistem</p>
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

        <!-- Filtre -->
        <div class="bg-white p-4 rounded shadow mb-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Caut</label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Titlu sau locaie..."
                           value="<?php echo htmlspecialchars($search_filter); ?>">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Toate</option>
                        <option value="upcoming" <?php echo $status_filter === 'upcoming' ? 'selected' : ''; ?>>Viitoare</option>
                        <option value="past" <?php echo $status_filter === 'past' ? 'selected' : ''; ?>>Trecute</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtreaz</button>
                </div>
            </form>
        </div>

        <!-- Statistici rapide -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="bg-white p-3 rounded shadow text-center">
                    <h4 class="text-primary mb-0"><?php echo count($events); ?></h4>
                    <small class="text-muted">Evenimente afiate</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="bg-white p-3 rounded shadow text-center">
                    <?php
                    $total_registrations = 0;
                    foreach ($events as $event) {
                        $total_registrations += $event['registrations_count'];
                    }
                    ?>
                    <h4 class="text-success mb-0"><?php echo $total_registrations; ?></h4>
                    <small class="text-muted">Total �nregistrri</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="bg-white p-3 rounded shadow text-center">
                    <?php
                    $upcoming_count = 0;
                    foreach ($events as $event) {
                        if (strtotime($event['date']) >= time()) {
                            $upcoming_count++;
                        }
                    }
                    ?>
                    <h4 class="text-info mb-0"><?php echo $upcoming_count; ?></h4>
                    <small class="text-muted">Evenimente viitoare</small>
                </div>
            </div>
        </div>

        <div class="bg-white rounded shadow">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">List evenimente</h5>
                    <a href="/admin/dashboard.php" class="btn btn-sm btn-secondary">�napoi la Dashboard</a>
                </div>

                <?php if (empty($events)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x" style="font-size: 4rem; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">Nu exist evenimente</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titlu</th>
                                    <th>Dat</th>
                                    <th>Locaie</th>
                                    <th>Organizator</th>
                                    <th>Categorie</th>
                                    <th>Participani</th>
                                    <th>Status</th>
                                    <th>Aciuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <?php
                                    $is_past = strtotime($event['date']) < time();
                                    $is_full = $event['max_participants'] > 0 && $event['registrations_count'] >= $event['max_participants'];
                                    ?>
                                    <tr>
                                        <td><?php echo $event['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($event['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                                        <td>
                                            <?php if (!empty($event['category'])): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($event['category']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($event['max_participants'] > 0) {
                                                echo $event['registrations_count'] . ' / ' . $event['max_participants'];
                                            } else {
                                                echo $event['registrations_count'];
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($is_past): ?>
                                                <span class="badge bg-secondary">Trecut</span>
                                            <?php elseif ($is_full): ?>
                                                <span class="badge bg-danger">Complet</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Activ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/events/view_event.php?id=<?php echo $event['id']; ?>"
                                                   class="btn btn-sm btn-primary" title="Detalii">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/events/edit_event.php?id=<?php echo $event['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Editeaz">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal<?php echo $event['id']; ?>"
                                                        title="terge">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Modal tergere -->
                                            <div class="modal fade" id="deleteModal<?php echo $event['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmare tergere</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                                <p>Eti sigur c vrei s tergi evenimentul <strong><?php echo htmlspecialchars($event['title']); ?></strong>?</p>
                                                                <?php if ($event['registrations_count'] > 0): ?>
                                                                    <p class="text-danger">
                                                                        <i class="bi bi-exclamation-triangle"></i>
                                                                        Acest eveniment are <?php echo $event['registrations_count']; ?> participani �nregistrai!
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuleaz</button>
                                                                <button type="submit" name="delete_event" class="btn btn-danger">terge</button>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
