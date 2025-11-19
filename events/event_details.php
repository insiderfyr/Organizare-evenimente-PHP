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

// Preia ID-ul evenimentului
if (!isset($_GET['id'])) {
    redirect('/events/list_events.php');
}

$event_id = (int)$_GET['id'];
$success = '';
$error = '';

// Procesare înregistrare/anulare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    if (isset($_POST['register'])) {
        // Verific dacă evenimentul este plin
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
            $error = "Evenimentul este plin!";
        } else {
            // Înregistrare la eveniment
            $stmt = $conn->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $event_id);

            if ($stmt->execute()) {
                $success = "Te-ai înregistrat cu succes la eveniment!";
            } else {
                if ($conn->errno === 1062) {
                    $error = "Ești deja înregistrat la acest eveniment!";
                } else {
                    $error = "Eroare la înregistrare!";
                }
            }
            $stmt->close();
        }
    } elseif (isset($_POST['unregister'])) {
        // Anulare înregistrare
        $stmt = $conn->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->bind_param("ii", $user_id, $event_id);

        if ($stmt->execute()) {
            $success = "Ai anulat înregistrarea cu succes!";
        } else {
            $error = "Eroare la anularea înregistrării!";
        }
        $stmt->close();
    }
}

// Preia detaliile evenimentului
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

// Verific dacă utilizatorul este înregistrat
$is_registered = false;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_registered = $result->num_rows > 0;
    $stmt->close();
}

// Număr participanți
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations_count = $result->fetch_assoc()['count'];
$stmt->close();

// Lista participanților
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

// Verificări
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
                <i class="bi bi-arrow-left"></i> Înapoi la evenimente
            </a>
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

        <div class="row">
            <!-- Detalii eveniment -->
            <div class="col-lg-8 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="mb-2"><?php echo htmlspecialchars($event['title']); ?></h2>
                            <?php if (!empty($event['category'])): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($event['category']); ?></span>
                            <?php endif; ?>
                            <?php if ($is_past): ?>
                                <span class="badge bg-secondary">Eveniment trecut</span>
                            <?php elseif ($is_full): ?>
                                <span class="badge bg-danger">Complet</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($is_organizer): ?>
                            <div class="btn-group">
                                <a href="/events/edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Editează
                                </a>
                                <a href="/events/delete_event.php?id=<?php echo $event['id']; ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Sigur vrei să ștergi acest eveniment?');">
                                    <i class="bi bi-trash"></i> Șterge
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5 class="mb-3">Descriere</h5>
                        <p class="text-muted" style="white-space: pre-line;"><?php echo htmlspecialchars($event['description']); ?></p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar text-primary me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Data și ora</small>
                                    <div><?php echo date('d.m.Y, H:i', strtotime($event['date'])); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt text-danger me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Locație</small>
                                    <div><?php echo htmlspecialchars($event['location']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person text-success me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Organizator</small>
                                    <div><?php echo htmlspecialchars($event['organizer_name']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people text-info me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <small class="text-muted">Participanți</small>
                                    <div>
                                        <?php
                                        if ($event['max_participants'] > 0) {
                                            echo $registrations_count . ' / ' . $event['max_participants'];
                                        } else {
                                            echo $registrations_count . ' (nelimitat)';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acțiuni pentru utilizatori -->
                    <?php if ($is_logged_in && !$is_organizer): ?>
                        <div class="mt-4">
                            <?php if ($is_registered): ?>
                                <form method="POST" action="">
                                    <button type="submit" name="unregister" class="btn btn-danger btn-lg w-100">
                                        <i class="bi bi-x-circle"></i> Anulează înregistrarea
                                    </button>
                                </form>
                            <?php elseif ($is_past): ?>
                                <button class="btn btn-secondary btn-lg w-100" disabled>
                                    Eveniment trecut
                                </button>
                            <?php elseif ($is_full): ?>
                                <button class="btn btn-danger btn-lg w-100" disabled>
                                    Eveniment complet
                                </button>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <button type="submit" name="register" class="btn btn-success btn-lg w-100">
                                        <i class="bi bi-check-circle"></i> Înregistrează-te
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$is_logged_in): ?>
                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle"></i>
                            Trebuie să fii autentificat pentru a te înregistra la acest eveniment.
                            <a href="/login.php" class="alert-link">Autentifică-te acum</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar cu participanți -->
            <div class="col-lg-4 mb-4">
                <div class="bg-white p-4 rounded shadow">
                    <h5 class="mb-3">Participanți (<?php echo count($participants); ?>)</h5>
                    <?php if (empty($participants)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-people" style="font-size: 2rem;"></i><br>
                            Nu există participanți înregistrați încă.
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
                                                Înregistrat: <?php echo date('d.m.Y', strtotime($participant['registration_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info organizator -->
                <div class="bg-white p-4 rounded shadow mt-4">
                    <h5 class="mb-3">Contact organizator</h5>
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
