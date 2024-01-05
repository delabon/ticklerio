<?php

require __DIR__ . '/../../parts/header.php';
?>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mt-5">Send Password-Reset Email</h1>

                <form action="/ajax/password-reset/" method="post" id="send-password-reset-email-form">
                    <div id="error-alert" class="alert alert-danger d-none" role="alert"></div>
                    <div id="success-alert" class="alert alert-success d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div>
                        <button id="send-reset-password-email-btn" type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../../parts/footer.php';
?>