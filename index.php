<?php
include('includes/header.php');
include('includes/navbar.php');
?>

<!-- Hero Section -->
<section class="hero-gradient text-center">
    <div class="container hero-content">
        <h1 class="hero-title text-white">Organizare Evenimente</h1>
        <p class="hero-subtitle text-white">Platformă pentru gestionarea evenimentelor și înregistrarea participanților</p>
        <div class="mt-4">
            <a href="events/list_events.php" class="btn btn-light btn-custom me-2">
                Vezi evenimente
            </a>
            <a href="register.php" class="btn btn-outline-light btn-custom">
                Creează cont
            </a>
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
                    <div class="stat-label">Evenimente</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">2.5K+</div>
                    <div class="stat-label">Participanți</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">45+</div>
                    <div class="stat-label">Organizatori</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5" style="padding: 60px 0;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-3" style="font-size: 2rem; font-weight: 600;">Funcționalități</h2>
            <p class="text-muted">Instrument simplu pentru gestionarea evenimentelor</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <h4 class="feature-title">Gestionare evenimente</h4>
                    <p class="feature-text">Creează și administrează evenimente. Urmărește participanții și generează rapoarte.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <h4 class="feature-title">Statistici</h4>
                    <p class="feature-text">Vezi statistici despre evenimente și participanți într-un dashboard simplu.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <h4 class="feature-title">Export rapoarte</h4>
                    <p class="feature-text">Exportă date în format CSV, Excel sau PDF pentru raportare.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section text-center">
    <div class="container">
        <h2 class="cta-title">Începe să organizezi evenimente</h2>
        <p class="lead mb-4" style="font-size: 1rem; opacity: 0.9;">Creează un cont pentru a avea acces la toate funcționalitățile.</p>
        <a href="register.php" class="btn btn-light btn-custom">
            Înregistrare
        </a>
    </div>
</section>

<?php
include('includes/footer.php');
?>