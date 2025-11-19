<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

$user_id = get_user_id();
$success = '';
$error = '';

// Process unsubscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unregister'])) {
    $event_id = (int)$_POST['event_id'];

    $stmt = $conn->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $user_id, $event_id);

    if ($stmt->execute()) {
        $success = "Unsubscription successful!";
    } else {
        $error = "Error during unsubscription!";
    }
    $stmt->close();
}

// Get all user registrations with event details
$stmt = $conn->prepare("
    SELECT
        e.id, e.title, e.description, e.date, e.location, e.category,
        e.max_participants, r.registration_date,
        u.username as organizer_name,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as current_participants
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN users u ON e.organizer_id = u.id
    WHERE r.user_id = ?
    ORDER BY e.date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations = [];
while ($row = $result->fetch_assoc()) {
    $registrations[] = $row;
}
$stmt->close();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="mb-4">
            <h2 class="mb-2">My Registrations</h2>
            <p class="text-muted">Events you are registered for</p>
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

        <?php if (empty($registrations)): ?>
            <div class="bg-white p-5 rounded shadow text-center">
                <p class="text-muted mb-4">You are not registered for any events at the moment.</p>
                <a href="/events/list_events.php" class="btn btn-primary">Explore Events</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($registrations as $reg): ?>
                <div class="col-md-6 mb-4">
                    <div class="bg-white rounded shadow h-100">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0"><?php echo htmlspecialchars($reg['title']); ?></h5>
                                <?php if (!empty($reg['category'])): ?>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($reg['category']); ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="text-muted small mb-3">
                                <?php echo htmlspecialchars(substr($reg['description'], 0, 150)) . (strlen($reg['description']) > 150 ? '...' : ''); ?>
                            </p>

                            <div class="mb-2">
                                <strong>📅 Date:</strong> <?php echo date('d.m.Y H:i', strtotime($reg['date'])); ?>
                            </div>
                            <div class="mb-2">
                                <strong>📍 Location:</strong> <?php echo htmlspecialchars($reg['location']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>👤 Organizer:</strong> <?php echo htmlspecialchars($reg['organizer_name']); ?>
                            </div>
                            <?php if ($reg['max_participants'] > 0): ?>
                            <div class="mb-2">
                                <strong>👥 Participants:</strong>
                                <?php echo $reg['current_participants'] . '/' . $reg['max_participants']; ?>
                            </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <small class="text-muted">Registered on: <?php echo date('d.m.Y', strtotime($reg['registration_date'])); ?></small>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="/events/view_event.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-info flex-grow-1">
                                    Details
                                </a>
                                <form method="POST" action="" class="flex-grow-1" onsubmit="return confirm('Are you sure you want to unsubscribe?');">
                                    <input type="hidden" name="event_id" value="<?php echo $reg['id']; ?>">
                                    <button type="submit" name="unregister" class="btn btn-sm btn-danger w-100">
                                        Unsubscribe
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
