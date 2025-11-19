<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Check if user has organizer or admin role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    redirect('/index.php');
}

// Get event ID
if (!isset($_GET['id'])) {
    redirect('/events/list_events.php');
}

$event_id = (int)$_GET['id'];
$user_id = get_user_id();
$user_role = $_SESSION['role'];

// Get event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    redirect('/events/list_events.php');
}

// Check if user is the event organizer or admin
if ($event['organizer_id'] != $user_id && $user_role !== 'admin') {
    redirect('/events/list_events.php');
}

// Count participants
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations_count = $result->fetch_assoc()['count'];
$stmt->close();

$error = '';

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Delete all registrations first
    $stmt = $conn->prepare("DELETE FROM registrations WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();

    // Then delete the event
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Event deleted successfully!";
        redirect('/events/list_events.php');
    } else {
        $error = "Error deleting event!";
    }
    $stmt->close();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="mb-4">
                    <a href="/events/view_event.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Back to Event
                    </a>
                    <h2 class="mb-2 text-danger">Delete Event</h2>
                    <p class="text-muted">This action is irreversible!</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-4 rounded shadow">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Warning!</strong> You are about to delete this event.
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3">Event Details</h5>
                        <table class="table">
                            <tr>
                                <th>Title:</th>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td><?php echo date('d.m.Y H:i', strtotime($event['date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                            </tr>
                            <tr>
                                <th>Registered Participants:</th>
                                <td>
                                    <span class="badge bg-info"><?php echo $registrations_count; ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php if ($registrations_count > 0): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i>
                            This event has <strong><?php echo $registrations_count; ?></strong> registered participants.
                            Deleting the event will cancel all registrations!
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="d-flex justify-content-between gap-2">
                            <a href="/events/view_event.php?id=<?php echo $event_id; ?>" class="btn btn-secondary flex-grow-1">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" name="confirm_delete" class="btn btn-danger flex-grow-1">
                                <i class="bi bi-trash"></i> Yes, Delete Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
