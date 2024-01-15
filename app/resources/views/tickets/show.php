<?php

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;
use App\Users\User;

require __DIR__ . '/../parts/header.php';

/** @var Ticket $ticket */
/** @var User $author */
?>

    <div class="container">
        <div class="row my-5">
            <div class="col-6">
                <h1 class="h3 my-0 mx-0"><?= $ticket->getTitle() ?></h1>
            </div>

            <div class="col-6 text-end">
                <?php if ($ticket->getUserId() === currentUserId() || isAdmin()) : ?>
                    <a href="/tickets/edit/<?= $ticket->getId() ?>" class="btn btn-primary">Edit</a>
                    <button id="delete-ticket-btn" data-id="<?= $ticket->getId() ?>" class="btn btn-danger">Delete</button>
                <?php endif ?>

                <?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
                    <a href="#reply-form" class="btn btn-secondary">Reply</a>
                <?php endif ?>
            </div>
        </div>

        <div>
            <div class="mb-3">
                <div><strong>Status:</strong> <span class="badge fs-6 text-bg-light"><?= $ticket->getStatus() ?></span></div>
                <div><strong>Author:</strong> <?= $author->getFirstName(); ?> <?= $author->getLastName(); ?></div>
                <div><strong>Created at:</strong> <?= date('Y-m-d H:i:s', $ticket->getCreatedAt()) ?></div>
                <div><strong>Updated at:</strong> <?= date('Y-m-d H:i:s', $ticket->getUpdatedAt()) ?></div>
            </div>

            <p><?= $ticket->getDescription() ?></p>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>