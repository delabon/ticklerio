<?php

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;

require __DIR__ . '/../parts/header.php';

/** @var Ticket $ticket */
?>

    <div class="container">
        <div class="row my-5">
            <div class="col-6">
                <h1 class="h3"><?= $ticket->getTitle() ?></h1>
            </div>

            <div class="col-6 text-end">
                <?php if ($ticket->getUserId() === currentUserId() || isAdmin()) : ?>
                    <a href="/tickets/edit/<?= $ticket->getId() ?>" class="btn btn-primary">Edit</a>
                    <button id="delete-ticket-btn" class="btn btn-danger">Delete</button>
                <?php endif ?>

                <?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
                    <a href="#reply-form" class="btn btn-secondary">Reply</a>
                <?php endif ?>
            </div>
        </div>

        <div class="mb-3">
            <p><?= $ticket->getDescription() ?></p>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>