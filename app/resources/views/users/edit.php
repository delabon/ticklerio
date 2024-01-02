<?php

use App\Users\User;

/** @var User $user */

require __DIR__ . '/../parts/header.php';
?>

    <div class="container">
        <h1 class="text-center mt-5">Account</h1>

        <form action="/ajax/user/update" method="post" id="account-form">
            <div id="account-error-alert" class="alert alert-danger d-none" role="alert"></div>
            <div id="account-success-alert" class="alert alert-success d-none" role="alert">
                Account updated successfully!
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="name" id="email" class="form-control" value="<?= $user->getEmail() ?>" aria-describedby="emailHelp" required>
            </div>
            <div class="mb-3">
                <label for="first-name" class="form-label">First name</label>
                <input type="text" name="first_name" id="first-name" class="form-control" value="<?= $user->getFirstName() ?>" required>
            </div>
            <div class="mb-3">
                <label for="last-name" class="form-label">Last name</label>
                <input type="text" name="last_name" id="last-name" class="form-control" value="<?= $user->getLastName() ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" value="" required>
            </div>
            <div class="mb-3">
                <button id="update-account-btn" type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>