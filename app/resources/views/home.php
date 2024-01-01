<?php

require __DIR__ . '/parts/header.php';
?>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mt-5"><?= $_ENV['APP_NAME'] ?></h1>

                <p class="text-center mt-5">
                    <a href="/register" class="btn btn-primary">Register</a>
                    <a href="/Login" class="btn btn-primary ms-3">Login</a>
                </p>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/parts/footer.php';
?>