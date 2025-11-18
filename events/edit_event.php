<?php
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Verific dac user-ul are rol de organizer sau admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    redirect('/index.php');
}

// Preia ID-ul evenimentului
if (!isset($_GET['id'])) {
    redirect('/events/list_events.php');
}

$event_id = (int)$_GET['id'];
$user_id = get_user_id();
$user_role = $_SESSION['role'];

// Preia detaliile evenimentului
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    redirect('/events/list_events.php');
}

// Verific dac utilizatorul este organizatorul evenimentului sau admin
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

    // Validare
    if (empty($title) || empty($description) || empty($date) || empty($location)) {
        $error = "Toate câmpurile obligatorii trebuie completate!";
    } else if ($max_participants < 0) {
        $error = "Numrul maxim de participani nu poate fi negativ!";
    } else {
        // Verific dac noul max_participants este mai mic decât numrul curent de înregistrri
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_registrations = $result->fetch_assoc()['count'];
        $stmt->close();

        if ($max_participants > 0 && $max_participants < $current_registrations) {
            $error = "Numrul maxim de participani nu poate fi mai mic decât numrul curent de înregistrri ($current_registrations)!";
        } else {
            // Actualizeaz evenimentul
            $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, date = ?, location = ?, category = ?, max_participants = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $title, $description, $date, $location, $category, $max_participants, $event_id);

            if ($stmt->execute()) {
                $success = "Eveniment actualizat cu succes!";
                // Reîncarc datele evenimentului
                $event['title'] = $title;
                $event['description'] = $description;
                $event['date'] = $date;
                $event['location'] = $location;
                $event['category'] = $category;
                $event['max_participants'] = $max_participants;
            } else {
                $error = "Eroare la actualizarea evenimentului!";
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
                    <a href="/events/event_details.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Înapoi la eveniment
                    </a>
                    <h2 class="mb-2">Editeaz eveniment</h2>
                    <p class="text-muted">Modific detaliile evenimentului</p>
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
                        <label for="title" class="form-label">Titlu eveniment *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?php echo htmlspecialchars($event['title']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descriere *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Data i ora *</label>
                        <input type="datetime-local" class="form-control" id="date" name="date"
                               value="<?php echo date('Y-m-d\TH:i', strtotime($event['date'])); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Locaie *</label>
                        <input type="text" class="form-control" id="location" name="location"
                               value="<?php echo htmlspecialchars($event['location']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Categorie</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">-- Selecteaz categorie --</option>
                            <option value="Workshop" <?php echo $event['category'] === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Conference" <?php echo $event['category'] === 'Conference' ? 'selected' : ''; ?>>Conferin</option>
                            <option value="Seminar" <?php echo $event['category'] === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Meetup" <?php echo $event['category'] === 'Meetup' ? 'selected' : ''; ?>>Meetup</option>
                            <option value="Other" <?php echo $event['category'] === 'Other' ? 'selected' : ''; ?>>Altele</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="max_participants" class="form-label">Numr maxim de participani</label>
                        <input type="number" class="form-control" id="max_participants" name="max_participants"
                               value="<?php echo (int)$event['max_participants']; ?>" min="0">
                        <small class="text-muted">0 = nelimitat</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/events/event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">Anuleaz</a>
                        <button type="submit" class="btn btn-primary">Salveaz modificrile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
