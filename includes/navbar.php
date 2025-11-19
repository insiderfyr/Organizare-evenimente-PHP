<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/index.php" style="color: #111827;">Event Organization</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/index.php" style="color: #4b5563;">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/events/list_events.php" style="color: #4b5563;">Events</a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Menu for logged in users -->
                    <li class="nav-item">
                        <a class="nav-link" href="/users/profile.php" style="color: #4b5563;">Profile</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link" style="color: #2563eb;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-danger text-danger ms-2 px-3" href="/logout.php" style="border-radius: 4px;">Logout</a>
                    </li>
                <?php else: ?>
                    <!-- Menu for visitors -->
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php" style="color: #4b5563;">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-2 px-3" href="/register.php" style="border-radius: 4px;">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
