<?php

use App\Tickets\Ticket;

require __DIR__ . '/../parts/header.php';

/** @var Ticket $ticket */
?>

    <div class="container">
        <h1 class="text-center mt-5"><?= $ticket->getTitle() ?></h1>

        <div class="mb-3">
            <p><?= $ticket->getDescription() ?></p>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>