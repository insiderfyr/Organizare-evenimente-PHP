<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Verifică dacă user-ul are rol de organizer sau admin
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

    // Validare
    if (empty($title) || empty($description) || empty($date) || empty($location)) {
        $error = "Toate câmpurile obligatorii trebuie completate!";
    } else if ($max_participants < 0) {
        $error = "Numărul maxim de participanți nu poate fi negativ!";
    } else {
        // Salvează evenimentul
        $stmt = $conn->prepare("INSERT INTO events (title, description, date, location, category, max_participants, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $title, $description, $date, $location, $category, $max_participants, $organizer_id);

        if ($stmt->execute()) {
            $success = "Eveniment creat cu succes!";
            // Reset form
            $title = $description = $date = $location = $category = '';
            $max_participants = 0;
        } else {
            $error = "Eroare la crearea evenimentului!";
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
                    <h2 class="mb-2">Creează eveniment nou</h2>
                    <p class="text-muted">Completează detaliile evenimentului</p>
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
                        <label for="title" class="form-label">Titlu eveniment *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descriere *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Data și ora *</label>
                        <input type="datetime-local" class="form-control" id="date" name="date"
                               value="<?php echo isset($date) ? htmlspecialchars($date) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Locație *</label>
                        <input type="text" class="form-control" id="location" name="location"
                               value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Categorie</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">-- Selectează categorie --</option>
                            <option value="Workshop" <?php echo (isset($category) && $category === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Conference" <?php echo (isset($category) && $category === 'Conference') ? 'selected' : ''; ?>>Conferință</option>
                            <option value="Seminar" <?php echo (isset($category) && $category === 'Seminar') ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Meetup" <?php echo (isset($category) && $category === 'Meetup') ? 'selected' : ''; ?>>Meetup</option>
                            <option value="Other" <?php echo (isset($category) && $category === 'Other') ? 'selected' : ''; ?>>Altele</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="max_participants" class="form-label">Număr maxim de participanți</label>
                        <input type="number" class="form-control" id="max_participants" name="max_participants"
                               value="<?php echo isset($max_participants) ? (int)$max_participants : 0; ?>" min="0">
                        <small class="text-muted">0 = nelimitat</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/events/list_events.php" class="btn btn-secondary">Anulează</a>
                        <button type="submit" class="btn btn-primary">Creează eveniment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
