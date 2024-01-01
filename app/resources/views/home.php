<?php

require __DIR__ . '/parts/header.php';
?>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mt-5"><?= $_ENV['APP_NAME'] ?></h1>

                <?php if (isLoggedIn()) : ?>
                    <p class="text-center mt-5">
                        <a href="/tickets" class="btn btn-primary">Tickets</a>
                        <a href="/logout" class="btn btn-secondary ms-3" id="logout-btn">Logout</a>
                    </p>
                <?php else : ?>
                    <p class="text-center mt-5">
                        <a href="/register" class="btn btn-primary">Register</a>
                        <a href="/Login" class="btn btn-primary ms-3">Login</a>
                    </p>
                <?php endif ?>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/parts/footer.php';
?>