<?php
session_start();
include('includes/header.php');
include('includes/navbar.php');
?>

<!-- Hero Section -->
<section class="hero-gradient text-center">
    <div class="container hero-content">
        <h1 class="hero-title text-white">Event Organization</h1>
        <p class="hero-subtitle text-white">Platform for event management and participant registration</p>
        <div class="mt-4">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="events/list_events.php" class="btn btn-light btn-custom me-2">
                    View Events
                </a>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'organizer')): ?>
                    <a href="events/create_event.php" class="btn btn-outline-light btn-custom">
                        Create Event
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="events/list_events.php" class="btn btn-light btn-custom me-2">
                    View Events
                </a>
                <a href="register.php" class="btn btn-outline-light btn-custom">
                    Create Account
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">150+</div>
                    <div class="stat-label">Events</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">2.5K+</div>
                    <div class="stat-label">Participants</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">45+</div>
                    <div class="stat-label">Organizers</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5" style="padding: 60px 0;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-3" style="font-size: 2rem; font-weight: 600;">Features</h2>
            <p class="text-muted">Simple tool for event management</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <h4 class="feature-title">Event Management</h4>
                    <p class="feature-text">Create and manage events. Track participants and generate reports.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <h4 class="feature-title">Statistics</h4>
                    <p class="feature-text">View statistics about events and participants in a simple dashboard.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <h4 class="feature-title">Export Reports</h4>
                    <p class="feature-text">Export data in CSV, Excel or PDF format for reporting.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section text-center">
    <div class="container">
        <?php if (isset($_SESSION['user_id'])): ?>
            <h2 class="cta-title">Discover Interesting Events</h2>
            <p class="lead mb-4" style="font-size: 1rem; opacity: 0.9;">View all available events and register.</p>
            <a href="events/list_events.php" class="btn btn-light btn-custom">
                View Events
            </a>
        <?php else: ?>
            <h2 class="cta-title">Start Organizing Events</h2>
            <p class="lead mb-4" style="font-size: 1rem; opacity: 0.9;">Create an account to access all features.</p>
            <a href="register.php" class="btn btn-light btn-custom">
                Register
            </a>
        <?php endif; ?>
    </div>
</section>

<?php
include('includes/footer.php');
?>