<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Initialize secure session
init_secure_session();

// Set security headers
set_security_headers();

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? get_user_id() : null;
$user_role = $is_logged_in ? get_user_role() : null;

// Get event ID
if (!isset($_GET['id'])) {
    redirect('/events/list_events.php');
}

$event_id = sanitize_int($_GET['id']);
$success = '';
$error = '';

// Process registration/cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $error = "Invalid security token. Please refresh the page and try again.";
        log_security_event('CSRF_VALIDATION_FAILED', "Event registration attempt with invalid CSRF token - Event ID: $event_id");
    }
    // Validate request origin
    else if (!validate_request_origin()) {
        $error = "Invalid request origin.";
        log_security_event('INVALID_REQUEST_ORIGIN', "Event registration from suspicious origin - Event ID: $event_id");
    }
    else if (isset($_POST['register'])) {
        // Check if event is full
        $stmt = $conn->prepare("
            SELECT e.max_participants, COUNT(r.id) as current_registrations
            FROM events e
            LEFT JOIN registrations r ON e.id = r.event_id
            WHERE e.id = ?
            GROUP BY e.id
        ");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event_capacity = $result->fetch_assoc();
        $stmt->close();

        $is_full = $event_capacity['max_participants'] > 0 &&
                   $event_capacity['current_registrations'] >= $event_capacity['max_participants'];

        if ($is_full) {
            $error = "Event is full!";
        } else {
            // Register for event
            $stmt = $conn->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $event_id);

            if ($stmt->execute()) {
                $success = "Successfully registered for the event!";
                log_security_event('EVENT_REGISTRATION', "User ID: $user_id registered for Event ID: $event_id");
            } else {
                if ($conn->errno === 1062) {
                    $error = "You are already registered for this event!";
                } else {
                    $error = "Registration error!";
                    log_security_event('REGISTRATION_ERROR', "Database error - User ID: $user_id, Event ID: $event_id");
                }
            }
            $stmt->close();
        }
    } elseif (isset($_POST['unregister'])) {
        // Cancel registration
        $stmt = $conn->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->bind_param("ii", $user_id, $event_id);

        if ($stmt->execute()) {
            $success = "Successfully cancelled registration!";
            log_security_event('EVENT_UNREGISTRATION', "User ID: $user_id unregistered from Event ID: $event_id");
        } else {
            $error = "Error cancelling registration!";
            log_security_event('UNREGISTRATION_ERROR', "Database error - User ID: $user_id, Event ID: $event_id");
        }
        $stmt->close();
    }
}

// Get event details
$stmt = $conn->prepare("
    SELECT
        e.id, e.title, e.description, e.date, e.location, e.category,
        e.max_participants, e.organizer_id, e.created_at,
        u.username as organizer_name, u.email as organizer_email
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    redirect('/events/list_events.php');
}

// Check if user is registered
$is_registered = false;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_registered = $result->num_rows > 0;
    $stmt->close();
}

// Count participants
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations_count = $result->fetch_assoc()['count'];
$stmt->close();

// Get participants list
$participants = [];
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.email, r.registration_date
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    WHERE r.event_id = ?
    ORDER BY r.registration_date DESC
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
}
$stmt->close();

// Checks
$is_past = strtotime($event['date']) < time();
$is_full = $event['max_participants'] > 0 && $registrations_count >= $event['max_participants'];
$is_organizer = $is_logged_in && ($event['organizer_id'] == $user_id || $user_role === 'admin');
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="mb-4">
            <a href="/events/list_events.php" class="btn btn-sm btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left"></i> Back to Events
            </a>
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

        <div class="row">
            <!-- Event details -->
            <div class="col-lg-8 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="mb-2"><?php echo htmlspecialchars($event['title']); ?></h2>
                            <?php if (!empty($event['category'])): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($event['category']); ?></span>
                            <?php endif; ?>
                            <?php if ($is_past): ?>
                                <span class="badge bg-secondary">Past Event</span>
                            <?php elseif ($is_full): ?>
                                <span class="badge bg-danger">Full</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($is_organizer): ?>
                            <div class="btn-group">
                                <a href="/events/edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="/events/delete_event.php?id=<?php echo $event['id']; ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this event?');">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5 class="mb-3">Description</h5>
                        <p class="text-muted" style="white-space: pre-line;"><?php echo htmlspecialchars($event['description']); ?></p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar text-primary me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Date and Time</small>
                                    <div><?php echo date('d.m.Y, H:i', strtotime($event['date'])); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt text-danger me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Location</small>
                                    <div><?php echo htmlspecialchars($event['location']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person text-success me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Organizer</small>
                                    <div><?php echo htmlspecialchars($event['organizer_name']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people text-info me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Participants</small>
                                    <div>
                                        <?php
                                        if ($event['max_participants'] > 0) {
                                            echo $registrations_count . ' / ' . $event['max_participants'];
                                        } else {
                                            echo $registrations_count . ' (unlimited)';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User actions -->
                    <?php if ($is_logged_in && !$is_organizer): ?>
                        <div class="mt-4">
                            <?php if ($is_registered): ?>
                                <form method="POST" action="">
                                    <?php echo csrf_token_field(); ?>
                                    <button type="submit" name="unregister" class="btn btn-danger btn-lg w-100">
                                        <i class="bi bi-x-circle"></i> Cancel Registration
                                    </button>
                                </form>
                            <?php elseif ($is_past): ?>
                                <button class="btn btn-secondary btn-lg w-100" disabled>
                                    Past Event
                                </button>
                            <?php elseif ($is_full): ?>
                                <button class="btn btn-danger btn-lg w-100" disabled>
                                    Event Full
                                </button>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <?php echo csrf_token_field(); ?>
                                    <button type="submit" name="register" class="btn btn-success btn-lg w-100">
                                        <i class="bi bi-check-circle"></i> Register
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$is_logged_in): ?>
                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle"></i>
                            You must be logged in to register for this event.
                            <a href="/login.php" class="alert-link">Sign in now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar with participants -->
            <div class="col-lg-4 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Participants (<?php echo count($participants); ?>)</h5>
                    <?php if (empty($participants)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-people" style="font-size: 2rem;"></i><br>
                            No participants registered yet.
                        </p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($participants as $participant): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="bi bi-person-circle" style="font-size: 2rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($participant['username']); ?></h6>
                                            <small class="text-muted">
                                                Registered: <?php echo date('d.m.Y', strtotime($participant['registration_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Organizer info -->
                <div class="bg-white p-4 rounded shadow mt-4">
                    <h5 class="mb-3">Contact Organizer</h5>
                    <div class="text-center">
                        <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
                        <h6 class="mt-2"><?php echo htmlspecialchars($event['organizer_name']); ?></h6>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-envelope"></i>
                            <?php echo htmlspecialchars($event['organizer_email']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
