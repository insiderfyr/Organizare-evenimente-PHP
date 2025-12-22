<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Check if user has organizer or admin role
if (!has_role('organizer')) {
    log_security_event('UNAUTHORIZED_ACCESS', 'Attempted access to create_event without proper role');
    redirect('/index.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $error = "Invalid security token. Please refresh the page and try again.";
        log_security_event('CSRF_VALIDATION_FAILED', 'Create event attempt with invalid CSRF token');
    }
    // Validate request origin
    else if (!validate_request_origin()) {
        $error = "Invalid request origin.";
        log_security_event('INVALID_REQUEST_ORIGIN', 'Create event from suspicious origin');
    }
    else {
        $title = sanitize($_POST['title']);
        $description = sanitize_text($_POST['description'], 2000); // Allow longer description
        $date = sanitize($_POST['date']);
        $location = sanitize($_POST['location']);
        $category = sanitize($_POST['category']);
        $max_participants = sanitize_int($_POST['max_participants']);
        $organizer_id = get_user_id();

        // Validation
        if (empty($title) || empty($description) || empty($date) || empty($location)) {
            $error = "All required fields must be completed!";
        } else if (strlen($title) < 5 || strlen($title) > 200) {
            $error = "Title must be between 5 and 200 characters!";
        } else if (strlen($description) < 10) {
            $error = "Description must be at least 10 characters!";
        } else if ($max_participants < 0) {
            $error = "Maximum number of participants cannot be negative!";
        } else if (!empty($category) && !in_array($category, ['Workshop', 'Conference', 'Seminar', 'Meetup', 'Other'])) {
            $error = "Invalid category selected!";
        } else {
            // Validate date is in the future
            $event_timestamp = strtotime($date);
            if ($event_timestamp < time()) {
                $error = "Event date must be in the future!";
            } else {
                // Save event
                $stmt = $conn->prepare("INSERT INTO events (title, description, date, location, category, max_participants, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssis", $title, $description, $date, $location, $category, $max_participants, $organizer_id);

                if ($stmt->execute()) {
                    $event_id = $stmt->insert_id;
                    $success = "Event created successfully!";
                    log_security_event('EVENT_CREATED', "Event ID: $event_id, Title: $title");
                    
                    // Reset form
                    $title = $description = $date = $location = $category = '';
                    $max_participants = 0;
                } else {
                    $error = "Error creating event!";
                    log_security_event('EVENT_CREATE_ERROR', "Database error");
                }
                $stmt->close();
            }
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
                    <a href="/events/list_events.php" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Back to Events
                    </a>
                    <h2 class="mb-2">Create New Event</h2>
                    <p class="text-muted">Fill in the event details</p>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="create_event.php" class="bg-white p-4 rounded shadow">
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
                               required minlength="5" maxlength="200">
                        <small class="text-muted">5-200 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  required minlength="10" maxlength="2000"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        <small class="text-muted">Minimum 10 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Date and Time *</label>
                        <input type="datetime-local" class="form-control" id="date" name="date"
                               value="<?php echo isset($date) ? htmlspecialchars($date) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location"
                               value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" 
                               required maxlength="255">
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
                               value="<?php echo isset($max_participants) ? (int)$max_participants : 0; ?>" min="0" max="10000">
                        <small class="text-muted">0 = unlimited</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/events/list_events.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
