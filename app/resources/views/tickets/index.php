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

                <ul class="list-group p-0 m-0">
                    <?php foreach ($tickets as $ticket) : ?>
                        <?php
                        $status = strtoupper(escape($ticket->getStatus()));
                        $status = $status === 'PUBLISH' ? 'NEW' : $status;
                        $statusClasses = 'bg-primary text-white';

                        if ($status === 'SOLVED') {
                            $statusClasses = 'bg-success text-white';
                        } elseif ($status === 'CLOSED') {
                            $statusClasses = 'bg-secondary text-white';
                        }
                        ?>
                        <li class="list-group-item py-3 px-4">
                            <div class="d-flex">
                                <i class="fa fa-file-o pull-left flex-grow-0 flex-shrink-0 d-inline-flex me-3"></i>
                                <div class="flex-grow-1">
                                    <a href="/tickets/<?= $ticket->getId() ?>" class="text-body-secondary"><strong><?= escape($ticket->getTitle()) ?></strong></a>
                                    <small class="<?= $statusClasses ?> p-1 rounded ms-2 fw-bold" style="font-size: 0.7rem;"><?= $status ?></small><span class="number pull-right"># <?= $ticket->getId() ?></span>
                                    <p class="info m-0 pt-1">Opened by <strong><?= $authors[$ticket->getId()]->getFirstName() ?> <?= $authors[$ticket->getId()]->getLastName() ?></strong> <?= date('M t, Y h:i a', $ticket->getCreatedAt()) ?></p>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php require __DIR__ . '/../parts/pagination.php'; ?>
            </div>
        </div>
    </div>
<?php
require __DIR__ . '/../parts/footer.php';
