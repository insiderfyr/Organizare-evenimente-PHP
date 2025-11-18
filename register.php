<?php
session_start();
require_once 'includes/functions.php';
require_once 'db/db_connect.php';

if (is_logged_in()){
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $error = '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Toate câmpurile sunt obligatorii!";
    }else if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Email invalid!";
    }else if ($password !== $confirm_password) {
        $error = "Parolele nu coincid!";
    }else if (strlen($username) < 3) {
        $error = "Username-ul trebuie să aibă minim 3 caractere!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username-ul sau email-ul există deja!";
        }
        $stmt->close();
    }

    // Dacă nu sunt erori, salvează în DB
    if (empty($error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            $stmt->close();
            redirect('login.php');
        } else {
            $error = "Eroare la crearea contului!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Înregistrare - EventManager</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Înregistrare</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error) && !empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="register.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?php echo isset($username) ? $username : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo isset($email) ? $email : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Parolă</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmă Parola</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Înregistrează-te</button>
                        </form>

                        <div class="mt-3 text-center">
                            <p>Ai deja cont? <a href="login.php">Autentifică-te aici</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
