<?php

require __DIR__ . '/parts/header.php';
?>

    <div class="hero-section">
        <div class="container">
            <h1 class="hero-title"><?= $_ENV['APP_NAME'] ?></h1>
            <p class="hero-subtitle">Streamline your customer support with an intuitive ticketing system designed to help teams resolve issues faster.</p>

            <?php if (isLoggedIn()) : ?>
                <div class="hero-actions">
                    <a href="/tickets" class="btn btn-primary btn-icon"><i class="fa-solid fa-list"></i> View Tickets</a>
                    <a href="/logout" class="btn btn-secondary btn-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                </div>
            <?php else : ?>
                <div class="hero-actions">
                    <a href="/register" class="btn btn-primary btn-icon"><i class="fa-solid fa-user-plus"></i> Get Started</a>
                    <a href="/login" class="btn btn-secondary btn-icon"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</a>
                </div>
            <?php endif ?>
        </div>
    </div>

<?php

require __DIR__ . '/parts/footer.php';
?>
