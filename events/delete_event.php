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

// Numr participani
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations_count = $result->fetch_assoc()['count'];
$stmt->close();

$error = '';

// Procesare tergere
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // terge mai întâi toate înregistrrile
    $stmt = $conn->prepare("DELETE FROM registrations WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();

    // Apoi terge evenimentul
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Eveniment ters cu succes!";
        redirect('/events/list_events.php');
    } else {
        $error = "Eroare la tergerea evenimentului!";
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
                    <a href="/events/event_details.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left"></i> Înapoi la eveniment
                    </a>
                    <h2 class="mb-2 text-danger">terge eveniment</h2>
                    <p class="text-muted">Aceast aciune este ireversibil!</p>
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
                        <strong>Atenie!</strong> Eti pe cale s tergi acest eveniment.
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3">Detalii eveniment</h5>
                        <table class="table">
                            <tr>
                                <th>Titlu:</th>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                            </tr>
                            <tr>
                                <th>Dat:</th>
                                <td><?php echo date('d.m.Y H:i', strtotime($event['date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Locaie:</th>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                            </tr>
                            <tr>
                                <th>Participani înregistrai:</th>
                                <td>
                                    <span class="badge bg-info"><?php echo $registrations_count; ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php if ($registrations_count > 0): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i>
                            Acest eveniment are <strong><?php echo $registrations_count; ?></strong> participani înregistrai.
                            tergerea evenimentului va anula toate înregistrrile!
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="d-flex justify-content-between gap-2">
                            <a href="/events/event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary flex-grow-1">
                                <i class="bi bi-x-circle"></i> Anuleaz
                            </a>
                            <button type="submit" name="confirm_delete" class="btn btn-danger flex-grow-1">
                                <i class="bi bi-trash"></i> Da, terge evenimentul
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
