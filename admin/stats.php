<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Verific dac user-ul este admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('/index.php');
}

// Statistici generale
$stats = [];

// Total utilizatori pe rol
$result = $conn->query("
    SELECT role, COUNT(*) as count
    FROM users
    GROUP BY role
");
$stats['users_by_role'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['users_by_role'][$row['role']] = $row['count'];
}

// Evenimente pe categorie
$result = $conn->query("
    SELECT category, COUNT(*) as count
    FROM events
    WHERE category IS NOT NULL AND category != ''
    GROUP BY category
    ORDER BY count DESC
");
$stats['events_by_category'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['events_by_category'][$row['category']] = $row['count'];
}

// Top 5 evenimente cu cele mai multe înregistrri
$result = $conn->query("
    SELECT e.id, e.title, e.date, COUNT(r.id) as registrations_count
    FROM events e
    LEFT JOIN registrations r ON e.id = r.event_id
    GROUP BY e.id
    ORDER BY registrations_count DESC
    LIMIT 5
");
$stats['top_events'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['top_events'][] = $row;
}

// Top 5 organizatori cu cele mai multe evenimente
$result = $conn->query("
    SELECT u.id, u.username, COUNT(e.id) as events_count
    FROM users u
    LEFT JOIN events e ON u.id = e.organizer_id
    WHERE u.role IN ('admin', 'organizer')
    GROUP BY u.id
    ORDER BY events_count DESC
    LIMIT 5
");
$stats['top_organizers'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['top_organizers'][] = $row;
}

// Evenimente pe lun (ultimele 6 luni)
$result = $conn->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM events
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$stats['events_by_month'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['events_by_month'][$row['month']] = $row['count'];
}

// Înregistrri pe lun (ultimele 6 luni)
$result = $conn->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM registrations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$stats['registrations_by_month'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['registrations_by_month'][$row['month']] = $row['count'];
}

// Rata de participare medie
$result = $conn->query("
    SELECT
        AVG(registrations_count) as avg_registrations
    FROM (
        SELECT e.id, COUNT(r.id) as registrations_count
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        GROUP BY e.id
    ) as event_stats
");
$stats['avg_registrations'] = round($result->fetch_assoc()['avg_registrations'], 2);

// Procent evenimente complete
$result = $conn->query("
    SELECT
        SUM(CASE WHEN registrations_count >= max_participants AND max_participants > 0 THEN 1 ELSE 0 END) as full_events,
        COUNT(*) as total_events
    FROM (
        SELECT e.id, e.max_participants, COUNT(r.id) as registrations_count
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.max_participants > 0
        GROUP BY e.id
    ) as event_stats
");
$full_events_data = $result->fetch_assoc();
$stats['full_events_percent'] = $full_events_data['total_events'] > 0
    ? round(($full_events_data['full_events'] / $full_events_data['total_events']) * 100, 2)
    : 0;
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">Statistici detaliate</h2>
                    <p class="text-muted">Analiz i rapoarte despre platform</p>
                </div>
                <a href="/admin/dashboard.php" class="btn btn-secondary">Înapoi la Dashboard</a>
            </div>
        </div>

        <!-- Statistici principale -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="bg-white p-4 rounded shadow h-100">
                    <h5 class="mb-3">Utilizatori pe rol</h5>
                    <?php if (empty($stats['users_by_role'])): ?>
                        <p class="text-muted">Nu exist date disponibile</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Rol</th>
                                        <th class="text-end">Numr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['users_by_role'] as $role => $count): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo $role === 'admin' ? 'danger' : ($role === 'organizer' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo htmlspecialchars($role); ?>
                                                </span>
                                            </td>
                                            <td class="text-end"><strong><?php echo $count; ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="bg-white p-4 rounded shadow h-100">
                    <h5 class="mb-3">Evenimente pe categorie</h5>
                    <?php if (empty($stats['events_by_category'])): ?>
                        <p class="text-muted">Nu exist date disponibile</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Categorie</th>
                                        <th class="text-end">Numr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['events_by_category'] as $category => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category); ?></td>
                                            <td class="text-end"><strong><?php echo $count; ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Metrici importante -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h4 class="text-primary mb-2"><?php echo $stats['avg_registrations']; ?></h4>
                    <p class="mb-0 text-muted">Medie participani per eveniment</p>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h4 class="text-success mb-2"><?php echo $stats['full_events_percent']; ?>%</h4>
                    <p class="mb-0 text-muted">Evenimente complete</p>
                </div>
            </div>
        </div>

        <!-- Top evenimente -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Top 5 evenimente populare</h5>
                    <?php if (empty($stats['top_events'])): ?>
                        <p class="text-muted">Nu exist date disponibile</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($stats['top_events'] as $index => $event): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h6>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y', strtotime($event['date'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-success">
                                            <?php echo $event['registrations_count']; ?> participani
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Top 5 organizatori activi</h5>
                    <?php if (empty($stats['top_organizers'])): ?>
                        <p class="text-muted">Nu exist date disponibile</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($stats['top_organizers'] as $index => $organizer): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-warning me-2">#<?php echo $index + 1; ?></span>
                                            <strong><?php echo htmlspecialchars($organizer['username']); ?></strong>
                                        </div>
                                        <span class="badge bg-info">
                                            <?php echo $organizer['events_count']; ?> evenimente
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Grafice temporale -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Evenimente create (ultimele 6 luni)</h5>
                    <?php if (empty($stats['events_by_month'])): ?>
                        <p class="text-muted text-center py-4">Nu exist date disponibile</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Lun</th>
                                        <th>Evenimente</th>
                                        <th>Grafic</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $max_events = max($stats['events_by_month']);
                                    foreach ($stats['events_by_month'] as $month => $count):
                                        $width = $max_events > 0 ? ($count / $max_events) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo date('M Y', strtotime($month . '-01')); ?></td>
                                            <td><strong><?php echo $count; ?></strong></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                         style="width: <?php echo $width; ?>%">
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

            <div class="col-lg-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Înregistrri (ultimele 6 luni)</h5>
                    <?php if (empty($stats['registrations_by_month'])): ?>
                        <p class="text-muted text-center py-4">Nu exist date disponibile</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Lun</th>
                                        <th>Înregistrri</th>
                                        <th>Grafic</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $max_registrations = max($stats['registrations_by_month']);
                                    foreach ($stats['registrations_by_month'] as $month => $count):
                                        $width = $max_registrations > 0 ? ($count / $max_registrations) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo date('M Y', strtotime($month . '-01')); ?></td>
                                            <td><strong><?php echo $count; ?></strong></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar"
                                                         style="width: <?php echo $width; ?>%">
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
    </div>
</section>

<?php include '../includes/footer.php'; ?>
