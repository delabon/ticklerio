<?php

use App\Users\User;
use App\Users\UserType;

require __DIR__ . '/../parts/header.php';

/** @var User $user */

$initials = strtoupper(substr($user->getFirstName(), 0, 1) . substr($user->getLastName(), 0, 1));
?>

    <div class="container">
        <div class="section-header">
            <h1><?= escape($user->getFirstName() . ' ' . $user->getLastName()) ?>'s profile</h1>
            <div class="d-flex gap-2">
                <?php if (isAdmin() && $user->getType() !== UserType::Deleted->value) : ?>
                    <?php if ($user->getType() === UserType::Banned->value) : ?>
                        <button id="unban-user-btn" data-id="<?= $user->getId() ?>" class="btn btn-secondary btn-sm btn-icon"><i class="fa-solid fa-unlock me-1"></i> Unban</button>
                    <?php else : ?>
                        <button id="ban-user-btn" data-id="<?= $user->getId() ?>" class="btn btn-danger btn-sm btn-icon"><i class="fa-solid fa-ban me-1"></i> Ban</button>
                    <?php endif; ?>
                <?php endif ?>

                <?php if ($user->getId() === currentUserId()) : ?>
                    <a href="/account" class="btn btn-primary btn-sm btn-icon"><i class="fa-solid fa-pen me-1"></i> Edit</a>
                    <button id="delete-user-btn" data-id="<?= $user->getId() ?>" class="btn btn-danger btn-sm btn-icon"><i class="fa-solid fa-trash me-1"></i> Delete</button>
                <?php endif ?>
            </div>
        </div>

        <div class="profile-page">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><?= $initials ?></div>
                    <div>
                        <div class="profile-name"><?= escape($user->getFirstName() . ' ' . $user->getLastName()) ?></div>
                    </div>
                </div>
                <div class="profile-details">
                    <div class="detail-item">
                        <span class="detail-label">Type:</span>
                        <span class="badge-type"><?= escape($user->getType()) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Registered:</span>
                        <span><?= date('M j, Y', $user->getCreatedAt()) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>
