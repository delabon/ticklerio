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
?>

    <div class="container">
        <div class="row my-5">
            <div class="col-6">
                <h1 class="h3 my-0 mx-0"><?= escape($ticket->getTitle()) ?></h1>
            </div>

            <div class="col-6 text-end">
                <?php if ($ticket->getUserId() === currentUserId() || isAdmin()) : ?>
                    <a href="/tickets/edit/<?= $ticket->getId() ?>" class="btn btn-primary">Edit</a>
                    <button id="delete-ticket-btn" data-id="<?= $ticket->getId() ?>" class="btn btn-danger">Delete</button>
                <?php endif ?>

                <?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
                    <a href="#reply-form" class="btn btn-secondary">Reply</a>
                <?php endif ?>

                <?php if (isAdmin()) : ?>
                    <select id="ticket-status" data-id="<?= $ticket->getId() ?>">
                        <?php foreach (TicketStatus::toArray() as $status) : ?>
                            <option value="<?= $status ?>" <?= $ticket->getStatus() === $status ? 'selected="selected"' : '' ?>><?= escape(ucfirst($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif ?>
            </div>
        </div>

        <div>
            <div class="mb-3">
                <div><strong>Status:</strong> <span class="badge fs-6 text-bg-light"><?= escape($ticket->getStatus()) ?></span></div>
                <div><strong>Author:</strong> <a href="/users/<?= $author->getId() ?>"><?= escape($author->getFirstName() . ' ' . $author->getLastName()) ?></a> </div>
                <div><strong>Created at:</strong> <?= date('Y-m-d H:i:s', $ticket->getCreatedAt()) ?></div>
                <div><strong>Updated at:</strong> <?= date('Y-m-d H:i:s', $ticket->getUpdatedAt()) ?></div>
            </div>

            <p><?= escape($ticket->getDescription()) ?></p>

            <div class="my-5">
                <h2 class="h4">Replies</h2>

                <?php foreach ($replies as $reply) : ?>
                    <div class="card reply-card mb-3" id="reply-<?= $reply->getId() ?>" data-id="<?= $reply->getId() ?>">
                        <div class="card-body">
                            <div class="mb-3">
                                <div><strong>Author:</strong> <a href="/users/<?= $reply->getUserId() ?>"><?= escape($replyAuthors[$reply->getUserId()]->getFirstName() . ' ' . $replyAuthors[$reply->getUserId()]->getLastName()) ?></a> </div>
                                <div><strong>Created at:</strong> <?= date('Y-m-d H:i:s', $reply->getCreatedAt()) ?></div>
                            </div>

                            <p class="reply-message"><?= escape($reply->getMessage()) ?></p>

                            <?php if ($reply->getUserId() === currentUserId() || isAdmin()) : ?>
                                <button class="btn btn-primary trigger-edit-reply-btn" data-id="<?= $reply->getId() ?>">Edit</button>
                                <button class="btn btn-danger delete-reply-btn" data-id="<?= $reply->getId() ?>">Delete</button>
                            <?php endif ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="reply-form">
                    <?php require __DIR__ . '/../replies/create-form.php' ?>
                    <?php require __DIR__ . '/../replies/edit-form.php' ?>
                </div>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>