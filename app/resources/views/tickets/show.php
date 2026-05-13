<?php

use App\Replies\Reply;
use App\Tickets\Ticket;
use App\Tickets\TicketStatus;
use App\Users\User;

require __DIR__ . '/../parts/header.php';

/** @var Ticket $ticket */
/** @var User $author */
/** @var Reply[] $replies */
/** @var User[] $replyAuthors */

$status = strtoupper(escape($ticket->getStatus()));
$status = $status === 'PUBLISH' ? 'NEW' : $status;
$badgeClass = 'badge-new';

if ($status === 'SOLVED') {
    $badgeClass = 'badge-solved';
} elseif ($status === 'CLOSED') {
    $badgeClass = 'badge-closed';
}
?>

    <div class="container">
        <div class="section-header">
            <h1 class="h3"><?= escape($ticket->getTitle()) ?></h1>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if ($ticket->getUserId() === currentUserId() || isAdmin()) : ?>
                    <a href="/tickets/edit/<?= $ticket->getId() ?>" class="btn btn-secondary btn-sm btn-icon"><i class="fa-solid fa-pen"></i> Edit</a>
                    <button id="delete-ticket-btn" data-id="<?= $ticket->getId() ?>" class="btn btn-danger btn-sm btn-icon"><i class="fa-solid fa-trash"></i> Delete</button>
                <?php endif ?>

                <?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
                    <a href="#reply-form" class="btn btn-primary btn-sm btn-icon"><i class="fa-solid fa-reply"></i> Reply</a>
                <?php endif ?>

                <?php if (isAdmin()) : ?>
                    <select id="ticket-status" data-id="<?= $ticket->getId() ?>" class="form-select form-select-sm" style="width: auto;">
                        <?php foreach (TicketStatus::toArray() as $status) : ?>
                            <option value="<?= $status ?>" <?= $ticket->getStatus() === $status ? 'selected="selected"' : '' ?>><?= escape(ucfirst($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body ticket-detail">
                <div class="ticket-info-grid">
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="<?= $badgeClass ?>"><?= $status ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Author:</span>
                        <span class="info-value"><a href="/users/<?= $author->getId() ?>"><?= escape($author->getFirstName() . ' ' . $author->getLastName()) ?></a></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Created:</span>
                        <span class="info-value"><?= date('M j, Y \a\t g:i a', $ticket->getCreatedAt()) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Updated:</span>
                        <span class="info-value"><?= date('M j, Y \a\t g:i a', $ticket->getUpdatedAt()) ?></span>
                    </div>
                </div>
                <div class="ticket-description">
                    <?= escape($ticket->getDescription()) ?>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h2 class="h5 mb-3"><i class="fa-solid fa-comments me-2"></i>Replies (<?= count($replies) ?>)</h2>

            <div class="reply-list">
                <?php foreach ($replies as $reply) : ?>
                    <div class="reply-item" id="reply-<?= $reply->getId() ?>" data-id="<?= $reply->getId() ?>">
                        <div class="reply-meta">
                            <a href="/users/<?= $reply->getUserId() ?>"><?= escape($replyAuthors[$reply->getId()]->getFirstName() . ' ' . $replyAuthors[$reply->getId()]->getLastName()) ?></a>
                            <span class="reply-date"><i class="fa-regular fa-clock me-1"></i><?= date('M j, Y \a\t g:i a', $reply->getCreatedAt()) ?></span>
                        </div>

                        <p class="reply-message"><?= escape($reply->getMessage()) ?></p>

                        <?php if ($reply->getUserId() === currentUserId() || isAdmin()) : ?>
                            <div class="reply-actions">
                                <button class="btn btn-secondary btn-sm trigger-edit-reply-btn" data-id="<?= $reply->getId() ?>"><i class="fa-solid fa-pen me-1"></i>Edit</button>
                                <button class="btn btn-danger btn-sm delete-reply-btn" data-id="<?= $reply->getId() ?>"><i class="fa-solid fa-trash me-1"></i>Delete</button>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="reply-form">
                <?php require __DIR__ . '/../replies/create-form.php' ?>
                <?php require __DIR__ . '/../replies/edit-form.php' ?>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>
