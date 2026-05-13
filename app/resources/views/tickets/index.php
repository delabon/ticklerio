<?php

use App\Tickets\Ticket;

require __DIR__ . '/../parts/header.php';

/** @var Ticket[] $tickets */
?>

    <div class="container">
        <div class="section-header">
            <h1><i class="fa-solid fa-ticket-simple me-2"></i>Tickets</h1>
            <a href="/tickets/create" class="btn btn-primary btn-icon"><i class="fa-solid fa-plus"></i> New Ticket</a>
        </div>

        <ul class="ticket-list">
            <?php foreach ($tickets as $ticket) : ?>
                <?php
                $status = strtoupper(escape($ticket->getStatus()));
                $status = $status === 'PUBLISH' ? 'NEW' : $status;
                $badgeClass = 'badge-new';

                if ($status === 'SOLVED') {
                    $badgeClass = 'badge-solved';
                } elseif ($status === 'CLOSED') {
                    $badgeClass = 'badge-closed';
                }
                ?>
                <li class="ticket-item">
                    <div class="ticket-icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div class="ticket-content">
                        <div class="ticket-title">
                            <a href="/tickets/<?= $ticket->getId() ?>"><?= escape($ticket->getTitle()) ?></a>
                            <span class="<?= $badgeClass ?>"><?= $status ?></span>
                        </div>
                        <div class="ticket-meta">
                            Opened by <strong><?= $authors[$ticket->getId()]->getFirstName() ?> <?= $authors[$ticket->getId()]->getLastName() ?></strong> &middot; <?= date('M j, Y \a\t g:i a', $ticket->getCreatedAt()) ?>
                        </div>
                    </div>
                    <div class="ticket-number">#<?= $ticket->getId() ?></div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php require __DIR__ . '/../parts/pagination.php'; ?>
    </div>
<?php
require __DIR__ . '/../parts/footer.php';
