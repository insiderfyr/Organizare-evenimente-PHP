<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Verify if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('/index.php');
}

// General statistics
$stats = [];

// Helper function to fetch all results from a prepared statement
function fetch_all($conn, $query, $params = [], $types = "") {
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Total users by role
$users_by_role_data = fetch_all($conn, "SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stats['users_by_role'] = array_column($users_by_role_data, 'count', 'role');

// Events by category
$events_by_category_data = fetch_all($conn, "
    SELECT category, COUNT(*) as count
    FROM events
    WHERE category IS NOT NULL AND category != ''
    GROUP BY category
    ORDER BY count DESC
");
$stats['events_by_category'] = array_column($events_by_category_data, 'count', 'category');

// Top 5 events with most registrations
$stats['top_events'] = fetch_all($conn, "
    SELECT e.id, e.title, e.date, COUNT(r.id) as registrations_count
    FROM events e
    LEFT JOIN registrations r ON e.id = r.event_id
    GROUP BY e.id
    ORDER BY registrations_count DESC
    LIMIT 5
");

// Top 5 organizers with most events
$stats['top_organizers'] = fetch_all($conn, "
    SELECT u.id, u.username, COUNT(e.id) as events_count
    FROM users u
    LEFT JOIN events e ON u.id = e.organizer_id
    WHERE u.role IN ('admin', 'organizer')
    GROUP BY u.id
    ORDER BY events_count DESC
    LIMIT 5
");

// Events by month (last 6 months)
$six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
$events_by_month_data = fetch_all($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM events
    WHERE created_at >= ?
    GROUP BY month
    ORDER BY month ASC
", [$six_months_ago], "s");
$stats['events_by_month'] = array_column($events_by_month_data, 'count', 'month');

// Registrations by month (last 6 months)
$registrations_by_month_data = fetch_all($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM registrations
    WHERE created_at >= ?
    GROUP BY month
    ORDER BY month ASC
", [$six_months_ago], "s");
$stats['registrations_by_month'] = array_column($registrations_by_month_data, 'count', 'month');


// Average participation rate
$avg_reg_data = fetch_all($conn, "
    SELECT AVG(registrations_count) as avg_registrations
    FROM (
        SELECT COUNT(r.id) as registrations_count
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        GROUP BY e.id
    ) as event_stats
");
$stats['avg_registrations'] = round($avg_reg_data[0]['avg_registrations'] ?? 0, 2);

// Percentage of full events
$full_events_data = fetch_all($conn, "
    SELECT
        SUM(CASE WHEN registrations_count >= max_participants AND max_participants > 0 THEN 1 ELSE 0 END) as full_events,
        COUNT(*) as total_events
    FROM (
        SELECT e.max_participants, COUNT(r.id) as registrations_count
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.max_participants > 0
        GROUP BY e.id
    ) as event_stats
")[0];
$stats['full_events_percent'] = ($full_events_data['total_events'] ?? 0) > 0
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
                    <h2 class="mb-2">Detailed statistics</h2>
                    <p class="text-muted">Platform analysis and reports</p>
                </div>
                <a href="/admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>

        <!-- Main statistics -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="bg-white p-4 rounded shadow h-100">
                    <h5 class="mb-3">Users by role</h5>
                    <?php if (empty($stats['users_by_role'])): ?>
                        <p class="text-muted">No data available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Role</th>
                                        <th class="text-end">Count</th>
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
                    <h5 class="mb-3">Events by category</h5>
                    <?php if (empty($stats['events_by_category'])): ?>
                        <p class="text-muted">No data available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Count</th>
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

        <!-- Important metrics -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h4 class="text-primary mb-2"><?php echo $stats['avg_registrations']; ?></h4>
                    <p class="mb-0 text-muted">Average participants per event</p>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h4 class="text-success mb-2"><?php echo $stats['full_events_percent']; ?>%</h4>
                    <p class="mb-0 text-muted">Full events</p>
                </div>
            </div>
        </div>

        <!-- Top events -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Top 5 popular events</h5>
                    <?php if (empty($stats['top_events'])): ?>
                        <p class="text-muted">No data available</p>
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
                                            <?php echo $event['registrations_count']; ?> participants
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
                    <h5 class="mb-3">Top 5 active organizers</h5>
                    <?php if (empty($stats['top_organizers'])): ?>
                        <p class="text-muted">No data available</p>
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
                                            <?php echo $organizer['events_count']; ?> events
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Temporal charts -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Events created (last 6 months)</h5>
                    <?php if (empty($stats['events_by_month'])): ?>
                        <p class="text-muted text-center py-4">No data available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Events</th>
                                        <th>Chart</th>
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
                    <h5 class="mb-3">Registrations (last 6 months)</h5>
                    <?php if (empty($stats['registrations_by_month'])): ?>
                        <p class="text-muted text-center py-4">No data available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Registrations</th>
                                        <th>Chart</th>
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
