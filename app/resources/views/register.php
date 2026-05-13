<?php

require __DIR__ . '/parts/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1 class="auth-title">Create Account</h1>

            <form action="/register" method="post" id="register-form">
                <div id="register-alert" class="alert alert-danger d-none" role="alert"></div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" name="name" id="email" class="form-control" placeholder="you@example.com" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first-name" class="form-label">First name</label>
                        <input type="text" name="first_name" id="first-name" class="form-control" placeholder="John" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last-name" class="form-label">Last name</label>
                        <input type="text" name="last_name" id="last-name" class="form-control" placeholder="Doe" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="8-20 characters" required>
                </div>
                <div class="mb-3">
                    <button id="register-btn" type="submit" class="btn btn-primary w-100">Create Account</button>
                </div>
            </form>

            <div class="auth-footer">
                <span>Already have an account? <a href="/login">Sign in</a></span>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/parts/footer.php';
?>
