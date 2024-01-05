<?php

require __DIR__ . '/parts/header.php';
?>

    <div class="container">
        <h1 class="text-center mt-5">Login</h1>

        <form action="/login" method="post" id="login_form">
            <div id="login-alert" class="alert alert-danger d-none" role="alert"></div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="name" id="email" class="form-control" aria-describedby="emailHelp">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control">
            </div>
            <div class="mb-3">
                <button id="btn-login" type="submit" class="btn btn-primary">Submit</button>
            </div>
            <div>
                <a href="/register">Register</a> -
                <a href="/password-reset">Reset password</a>
            </div>
        </form>
    </div>

<?php

require __DIR__ . '/parts/footer.php';
?>