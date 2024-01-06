<?php

use App\Tickets\Ticket;

require __DIR__ . '/../parts/header.php';

/** @var Ticket $ticket */
?>

    <div class="container">
        <div class="row my-5">
            <div class="col-6">
                <h1 class="h3"><?= $ticket->getTitle() ?></h1>
            </div>
            <div class="col-6 text-end">
                <a href="/tickets/edit/<?= $ticket->getId() ?>" class="btn btn-primary">Edit</a>
                <button id="delete-ticket-btn" class="btn btn-danger">Delete</button>
            </div>
        </div>

        <div class="mb-3">
            <p><?= $ticket->getDescription() ?></p>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>