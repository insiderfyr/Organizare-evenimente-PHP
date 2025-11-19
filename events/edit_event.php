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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $date = sanitize($_POST['date']);
    $location = sanitize($_POST['location']);
    $category = sanitize($_POST['category']);
    $max_participants = (int)$_POST['max_participants'];

    // Validation
    if (empty($title) || empty($description) || empty($date) || empty($location)) {
        $error = "All required fields must be completed!";
    } else if ($max_participants < 0) {
        $error = "Maximum number of participants cannot be negative!";
    } else {
        // Check if new max_participants is less than current registrations
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_registrations = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($max_participants > 0 && $max_participants < $current_registrations) {
            $error = "Maximum number of participants cannot be less than current registrations ($current_registrations)!";
        } else {
            // Update event
            $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, date = ?, location = ?, category = ?, max_participants = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $title, $description, $date, $location, $category, $max_participants, $event_id);

            if ($stmt->execute()) {
                $success = "Event updated successfully!";
                // Reload event data
                $event['title'] = $title;
                $event['description'] = $description;
                $event['date'] = $date;
                $event['location'] = $location;
                $event['category'] = $category;
                $event['max_participants'] = $max_participants;
            } else {
                $error = "Error updating event!";
            }
            $stmt->close();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="mb-4">
                    <a href="/events/view_event.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Back to Event
                    </a>
                    <h2 class="mb-2">Edit Event</h2>
                    <p class="text-muted">Modify event details</p>
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

                <form method="POST" action="" class="bg-white p-4 rounded shadow">
                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?php echo htmlspecialchars($event['title']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Date and Time *</label>
                        <input type="datetime-local" class="form-control" id="date" name="date"
                               value="<?php echo date('Y-m-d\TH:i', strtotime($event['date'])); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location"
                               value="<?php echo htmlspecialchars($event['location']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">-- Select category --</option>
                            <option value="Workshop" <?php echo $event['category'] === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Conference" <?php echo $event['category'] === 'Conference' ? 'selected' : ''; ?>>Conference</option>
                            <option value="Seminar" <?php echo $event['category'] === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Meetup" <?php echo $event['category'] === 'Meetup' ? 'selected' : ''; ?>>Meetup</option>
                            <option value="Other" <?php echo $event['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="max_participants" class="form-label">Maximum Number of Participants</label>
                        <input type="number" class="form-control" id="max_participants" name="max_participants"
                               value="<?php echo (int)$event['max_participants']; ?>" min="0">
                        <small class="text-muted">0 = unlimited</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/events/view_event.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
