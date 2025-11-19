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

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total events
$result = $conn->query("SELECT COUNT(*) as total FROM events");
$stats['total_events'] = $result->fetch_assoc()['total'];

// Total registrations
$result = $conn->query("SELECT COUNT(*) as total FROM registrations");
$stats['total_registrations'] = $result->fetch_assoc()['total'];

// Total organizers
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role IN ('admin', 'organizer')");
$stats['total_organizers'] = $result->fetch_assoc()['total'];

// Upcoming events
$result = $conn->query("SELECT COUNT(*) as total FROM events WHERE date >= NOW()");
$stats['upcoming_events'] = $result->fetch_assoc()['total'];

// New users (last 30 days)
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['new_users'] = $result->fetch_assoc()['total'];

// Last 5 created events
$recent_events = [];
$result = $conn->query("
    SELECT e.id, e.title, e.date, e.location, u.username as organizer
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    ORDER BY e.created_at DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recent_events[] = $row;
}

// Last 5 registered users
$recent_users = [];
$result = $conn->query("
    SELECT id, username, email, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="mb-4">
            <h2 class="mb-2">Admin Dashboard</h2>
            <p class="text-muted">Administration control panel</p>
        </div>

        <!-- Main statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h3 class="text-primary mb-2"><?php echo $stats['total_users']; ?></h3>
                    <p class="mb-0 text-muted">Total users</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h3 class="text-success mb-2"><?php echo $stats['total_events']; ?></h3>
                    <p class="mb-0 text-muted">Total events</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h3 class="text-info mb-2"><?php echo $stats['total_registrations']; ?></h3>
                    <p class="mb-0 text-muted">Total registrations</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h3 class="text-warning mb-2"><?php echo $stats['upcoming_events']; ?></h3>
                    <p class="mb-0 text-muted">Upcoming events</p>
                </div>
            </div>
        </div>

        <!-- Secondary statistics -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h4 class="text-secondary mb-2"><?php echo $stats['total_organizers']; ?></h4>
                    <p class="mb-0 text-muted">Organizers and Admins</p>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="bg-white p-4 rounded shadow text-center">
                    <h4 class="text-secondary mb-2"><?php echo $stats['new_users']; ?></h4>
                    <p class="mb-0 text-muted">New users (30 days)</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent events -->
            <div class="col-md-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Recent events</h5>
                    <?php if (empty($recent_events)): ?>
                        <p class="text-muted">No events yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_events as $event): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                        <small class="text-muted">
                                            Date: <?php echo date('d.m.Y H:i', strtotime($event['date'])); ?><br>
                                            Location: <?php echo htmlspecialchars($event['location']); ?><br>
                                            Organizer: <?php echo htmlspecialchars($event['organizer']); ?>
                                        </small>
                                    </div>
                                    <a href="/events/view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent users -->
            <div class="col-md-6 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Recent users</h5>
                    <?php if (empty($recent_users)): ?>
                        <p class="text-muted">No users yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_users as $user): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h6>
                                        <small class="text-muted">
                                            Email: <?php echo htmlspecialchars($user['email']); ?><br>
                                            Role: <?php echo htmlspecialchars($user['role']); ?><br>
                                            Joined: <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'organizer' ? 'warning' : 'secondary'); ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick links -->
        <div class="row">
            <div class="col-12">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Quick actions</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/admin/users.php" class="btn btn-primary">Manage users</a>
                        <a href="/admin/manage_events.php" class="btn btn-success">Manage events</a>
                        <a href="/admin/stats.php" class="btn btn-info">Detailed statistics</a>
                        <a href="/admin/reports.php" class="btn btn-warning">Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
