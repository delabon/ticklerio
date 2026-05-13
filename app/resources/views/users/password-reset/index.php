<?php

require __DIR__ . '/../../parts/header.php';
?>

    <div class="auth-page">
        <div class="auth-card">
            <h1 class="auth-title"><i class="fa-solid fa-envelope me-2"></i>Send Password-Reset Email</h1>

            <form action="/ajax/password-reset/" method="post" id="send-password-reset-email-form">
                <div id="error-alert" class="alert alert-danger d-none" role="alert"></div>
                <div id="success-alert" class="alert alert-success d-none" role="alert"></div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div>
                    <button id="send-reset-password-email-btn" type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-paper-plane me-1"></i> Send Reset Link</button>
                </div>
            </form>
        </div>
    </div>

<?php

require __DIR__ . '/../../parts/footer.php';
?>
