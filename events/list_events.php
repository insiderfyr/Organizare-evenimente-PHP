<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

if (!isset($_SESSION)) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? get_user_id() : null;
$user_role = $is_logged_in ? $_SESSION['role'] : null;

// Filtre
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search_filter = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? sanitize($_GET['date_filter']) : 'all';

// Construire query
$query = "
    SELECT
        e.id, e.title, e.description, e.date, e.location, e.category,
        e.max_participants, e.organizer_id, u.username as organizer_name,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as registrations_count
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    WHERE 1=1
";

$params = [];
$types = '';

// Aplicare filtre
if (!empty($category_filter)) {
    $query .= " AND e.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($search_filter)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $search_param = '%' . $search_filter . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($date_filter === 'upcoming') {
    $query .= " AND e.date >= NOW()";
} else if ($date_filter === 'past') {
    $query .= " AND e.date < NOW()";
}

$query .= " ORDER BY e.date ASC";

// Execută query
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">Events</h2>
                    <p class="text-muted">Discover and participate in events</p>
                </div>
                <?php if ($is_logged_in && ($user_role === 'admin' || $user_role === 'organizer')): ?>
                    <a href="/events/create_event.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Event
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded shadow mb-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Title, description or location..."
                           value="<?php echo htmlspecialchars($search_filter); ?>">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-control" id="category" name="category">
                        <option value="">All</option>
                        <option value="Workshop" <?php echo $category_filter === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                        <option value="Conference" <?php echo $category_filter === 'Conference' ? 'selected' : ''; ?>>Conference</option>
                        <option value="Seminar" <?php echo $category_filter === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                        <option value="Meetup" <?php echo $category_filter === 'Meetup' ? 'selected' : ''; ?>>Meetup</option>
                        <option value="Other" <?php echo $category_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_filter" class="form-label">Period</label>
                    <select class="form-control" id="date_filter" name="date_filter">
                        <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="upcoming" <?php echo $date_filter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="past" <?php echo $date_filter === 'past' ? 'selected' : ''; ?>>Past</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <!-- Event list -->
        <?php if (empty($events)): ?>
            <div class="bg-white p-5 rounded shadow text-center">
                <i class="bi bi-calendar-x" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3 text-muted">No events found</h4>
                <p class="text-muted">
                    <?php if (!empty($search_filter) || !empty($category_filter) || $date_filter !== 'all'): ?>
                        Try modifying the search filters.
                    <?php else: ?>
                        No events available at the moment.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($events as $event): ?>
                    <?php
                    $is_past = strtotime($event['date']) < time();
                    $is_full = $event['max_participants'] > 0 && $event['registrations_count'] >= $event['max_participants'];
                    $is_organizer = $is_logged_in && ($event['organizer_id'] == $user_id || $user_role === 'admin');
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm hover-shadow">
                            <?php if ($is_past): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-secondary">Past</span>
                                </div>
                            <?php elseif ($is_full): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger">Full</span>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>

                                <?php if (!empty($event['category'])): ?>
                                    <span class="badge bg-info mb-2"><?php echo htmlspecialchars($event['category']); ?></span>
                                <?php endif; ?>

                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?>
                                </p>

                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($event['date'])); ?>
                                    </small>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                    </small>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($event['organizer_name']); ?>
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-people"></i>
                                        <?php
                                        if ($event['max_participants'] > 0) {
                                            echo $event['registrations_count'] . ' / ' . $event['max_participants'] . ' participants';
                                        } else {
                                            echo $event['registrations_count'] . ' participants';
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>

                            <div class="card-footer bg-white border-top-0">
                                <div class="d-flex gap-2">
                                    <a href="/events/view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary flex-grow-1">
                                        Details
                                    </a>
                                    <?php if ($is_organizer): ?>
                                        <a href="/events/edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/events/delete_event.php?id=<?php echo $event['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this event?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.hover-shadow {
    transition: box-shadow 0.3s ease;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

<?php include '../includes/footer.php'; ?>
