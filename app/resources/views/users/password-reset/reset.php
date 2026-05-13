<?php

require __DIR__ . '/../../parts/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1 class="auth-title"><i class="fa-solid fa-key me-2"></i>Reset Password</h1>

            <form action="/ajax/password-reset/" method="post" id="reset-password-form">
                <input type="hidden" id="reset_password_token" name="reset_password_token" value="<?= $_GET['token'] ?? '' ?>">

                <div id="error-alert" class="alert alert-danger d-none" role="alert"></div>
                <div id="success-alert" class="alert alert-success d-none" role="alert"></div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">New password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="8-20 characters">
                </div>

                <div class="mb-3">
                    <label for="password_match" class="form-label">Confirm password</label>
                    <input type="password" class="form-control" id="password_match" name="password_match" placeholder="Re-enter your password">
                </div>

                <div>
                    <button id="reset-password-btn" type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-check me-1"></i> Reset Password</button>
                </div>
            </form>
        </div>
    </div>

<?php

require __DIR__ . '/../../parts/footer.php';
?>
