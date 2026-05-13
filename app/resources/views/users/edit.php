<?php

use App\Users\User;

/** @var User $user */

require __DIR__ . '/../parts/header.php';
?>

    <div class="form-page">
        <div class="form-card">
            <h1><i class="fa-regular fa-user me-2"></i>Account</h1>

            <form action="/ajax/user/update" method="post" id="account-form">
                <div id="account-error-alert" class="alert alert-danger d-none" role="alert"></div>
                <div id="account-success-alert" class="alert alert-success d-none">
                    Account updated successfully!
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" name="name" id="email" class="form-control" value="<?= $user->getEmail() ?>" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first-name" class="form-label">First name</label>
                        <input type="text" name="first_name" id="first-name" class="form-control" value="<?= $user->getFirstName() ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last-name" class="form-label">Last name</label>
                        <input type="text" name="last_name" id="last-name" class="form-control" value="<?= $user->getLastName() ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" value="" required placeholder="Confirm your password">
                </div>
                <div class="mb-3">
                    <button id="update-account-btn" type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i> Update Account</button>
                </div>
            </form>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>
