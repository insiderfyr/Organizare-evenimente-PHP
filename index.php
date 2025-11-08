<?php
include('includes/header.php');
include('includes/navbar.php');
?>

<!-- Hero Section -->
<section class="hero-gradient text-center">
    <div class="container hero-content">
        <h1 class="hero-title text-white">Organizează evenimente<br>ușor și rapid!</h1>
        <p class="hero-subtitle text-white">Platforma ta completă pentru gestionarea evenimentelor,<br>invitațiilor și raportărilor.</p>
        <div class="mt-4">
            <a href="events/list_events.php" class="btn btn-light btn-custom me-3">
                Vezi evenimente
            </a>
            <a href="pages/about.php" class="btn btn-outline-light btn-custom">
                Despre aplicație
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
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Evenimente organizate</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Participanți fericiți</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Rată de satisfacție</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5" style="padding: 80px 0;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">De ce să ne alegi?</h2>
            <p class="text-muted">Totul de care ai nevoie pentru evenimente perfecte</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        📊
                    </div>
                    <h4 class="feature-title">Administrare completă</h4>
                    <p class="feature-text">Gestionează evenimente, participanți și rapoarte dintr-un singur loc. Totul organizat și la îndemână.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        📈
                    </div>
                    <h4 class="feature-title">Statistici în timp real</h4>
                    <p class="feature-text">Monitorizează performanța evenimentelor cu grafice interactive și rapoarte detaliate.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        💬
                    </div>
                    <h4 class="feature-title">Contact ușor</h4>
                    <p class="feature-text">Trimite mesaje direct din secțiunea de contact și primește suport rapid de la echipa noastră.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section text-center">
    <div class="container">
        <h2 class="cta-title">Începe chiar acum!</h2>
        <p class="lead mb-4" style="font-size: 1.2rem; opacity: 0.95;">Creează un cont gratuit și începe să organizezi evenimentele tale astăzi.</p>
        <a href="register.php" class="btn btn-light btn-custom" style="font-size: 1.2rem;">
            Creează cont gratuit
        </a>
    </div>
</section>

<?php
include('includes/footer.php');
?>