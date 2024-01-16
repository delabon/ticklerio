<?php

use App\Users\User;
use App\Users\UserType;

require __DIR__ . '/../parts/header.php';

/** @var User $user */
?>

    <div class="container">
        <div class="row my-5">
            <div class="col-6">
                <h1 class="h3 my-0 mx-0"><?= escape($user->getFirstName() . ' ' . $user->getLastName()) ?>'s profile</h1>
            </div>

            <div class="col-6 text-end">
                <?php if (isAdmin() && $user->getType() !== UserType::Deleted->value) : ?>
                    <?php if ($user->getType() === UserType::Banned->value) : ?>
                        <button id="unban-user-btn" data-id="<?= $user->getId() ?>" class="btn btn-secondary">Unban</button>
                    <?php else : ?>
                        <button id="ban-user-btn" data-id="<?= $user->getId() ?>" class="btn btn-danger">Ban</button>
                    <?php endif; ?>
                <?php endif ?>

                <?php if ($user->getId() === currentUserId()) : ?>
                    <a href="/account" class="btn btn-primary">Edit</a>
                    <button id="delete-user-btn" data-id="<?= $user->getId() ?>" class="btn btn-danger">Delete</button>
                <?php endif ?>
            </div>
        </div>

        <div>
            <div class="mb-3">
                <div><strong>Type:</strong> <span class="badge fs-6 text-bg-light"><?= escape($user->getType()) ?></span></div>
                <div><strong>Registered at:</strong> <?= date('Y-m-d H:i:s', $user->getCreatedAt()) ?></div>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>