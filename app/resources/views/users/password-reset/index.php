<?php

require __DIR__ . '/../../parts/header.php';
?>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mt-5">Reset Password</h1>

                <form action="/ajax/password-reset/" method="post">
                    <input type="hidden" name="reset_password_token" value="<?= $_GET['token'] ?>">

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>

                    <div class="mb-3">
                        <label for="password_match" class="form-label">Password match</label>
                        <input type="password" class="form-control" id="password_match" name="password_match">
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Reset password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../../parts/footer.php';
?>