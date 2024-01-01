<?php

require __DIR__ . '/parts/header.php';
?>

    <div class="container">
        <h1 class="text-center mt-5">Register</h1>

        <form action="/register" method="post" id="register-form">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?= csrf() ?>">

            <div id="register-alert" class="alert alert-danger d-none" role="alert"></div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="name" id="email" class="form-control" aria-describedby="emailHelp" required>
            </div>
            <div class="mb-3">
                <label for="first-name" class="form-label">First name</label>
                <input type="text" name="first_name" id="first-name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="last-name" class="form-label">Last name</label>
                <input type="text" name="last_name" id="last-name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <button id="register-btn" type="submit" class="btn btn-primary">Submit</button>
            </div>
            <div>
                <a href="/login">Login</a>
            </div>
        </form>
    </div>

<?php

require __DIR__ . '/parts/footer.php';
?>