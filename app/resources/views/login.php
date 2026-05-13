<?php

require __DIR__ . '/parts/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1 class="auth-title">Sign In</h1>

            <form action="/login" method="post" id="login_form">
                <div id="login-alert" class="alert alert-danger d-none" role="alert"></div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" name="name" id="email" class="form-control" placeholder="you@example.com">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password">
                </div>
                <div class="mb-3">
                    <button id="btn-login" type="submit" class="btn btn-primary w-100">Sign In</button>
                </div>
            </form>

            <div class="auth-footer">
                <span>Don't have an account? <a href="/register">Create one</a></span>
                <br>
                <a href="/password-reset">Forgot your password?</a>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/parts/footer.php';
?>
