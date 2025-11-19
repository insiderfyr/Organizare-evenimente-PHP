<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Check if user has organizer or admin role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    redirect('/index.php');
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
    $organizer_id = get_user_id();

    // Validation
    if (empty($title) || empty($description) || empty($date) || empty($location)) {
        $error = "All required fields must be completed!";
    } else if ($max_participants < 0) {
        $error = "Maximum number of participants cannot be negative!";
    } else {
        // Save event
        $stmt = $conn->prepare("INSERT INTO events (title, description, date, location, category, max_participants, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $title, $description, $date, $location, $category, $max_participants, $organizer_id);

        if ($stmt->execute()) {
            $success = "Event created successfully!";
            // Reset form
            $title = $description = $date = $location = $category = '';
            $max_participants = 0;
        } else {
            $error = "Error creating event!";
        }
        $stmt->close();
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
                    <h2 class="mb-2">Create New Event</h2>
                    <p class="text-muted">Fill in the event details</p>
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

                <form method="POST" action="create_event.php" class="bg-white p-4 rounded shadow">
                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Date and Time *</label>
                        <input type="datetime-local" class="form-control" id="date" name="date"
                               value="<?php echo isset($date) ? htmlspecialchars($date) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location"
                               value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">-- Select category --</option>
                            <option value="Workshop" <?php echo (isset($category) && $category === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Conference" <?php echo (isset($category) && $category === 'Conference') ? 'selected' : ''; ?>>Conference</option>
                            <option value="Seminar" <?php echo (isset($category) && $category === 'Seminar') ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Meetup" <?php echo (isset($category) && $category === 'Meetup') ? 'selected' : ''; ?>>Meetup</option>
                            <option value="Other" <?php echo (isset($category) && $category === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="max_participants" class="form-label">Maximum Number of Participants</label>
                        <input type="number" class="form-control" id="max_participants" name="max_participants"
                               value="<?php echo isset($max_participants) ? (int)$max_participants : 0; ?>" min="0">
                        <small class="text-muted">0 = unlimited</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/events/list_events.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
