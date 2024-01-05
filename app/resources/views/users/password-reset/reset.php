<?php

require __DIR__ . '/../../parts/header.php';
?>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mt-5">Reset Password</h1>

                <form action="/ajax/password-reset/" method="post" id="reset-password-form">
                    <input type="hidden" id="reset_password_token" name="reset_password_token" value="<?= $_GET['token'] ?? '' ?>">

                    <div id="error-alert" class="alert alert-danger d-none" role="alert"></div>
                    <div id="success-alert" class="alert alert-success d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>

                    <div class="mb-3">
                        <label for="password_match" class="form-label">Password match</label>
                        <input type="password" class="form-control" id="password_match" name="password_match">
                    </div>

                    <div>
                        <button id="reset-password-btn" type="submit" class="btn btn-primary">Reset password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../../parts/footer.php';
?>