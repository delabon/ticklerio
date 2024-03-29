<?php

use App\Tickets\Ticket;

require __DIR__ . '/../parts/header.php';

/** @var Ticket[] $tickets */
?>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="row my-5">
                    <div class="col-6">
                        <h1>Tickets</h1>
                    </div>
                    <div class="col-6 text-end">
                        <a href="/tickets/create" class="btn btn-primary">Create a ticket</a>
                    </div>
                </div>

                <?php foreach ($tickets as $ticket) : ?>
                    <div class="mb-2">
                        <h3 class="mb-2 h4">
                            <a href="/tickets/<?= $ticket->getId() ?>"><?= escape($ticket->getTitle()) ?></a>
                            <span class="badge fs-6 text-bg-light"><?= escape($ticket->getStatus()) ?></span>
                        </h3>
                    </div>
                    <hr>
                <?php endforeach; ?>

                <?php require __DIR__ . '/../parts/pagination.php'; ?>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>