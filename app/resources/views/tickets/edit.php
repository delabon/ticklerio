<?php

use App\Tickets\Ticket;

require __DIR__ . '/../parts/header.php';

/** @var Ticket $ticket */
?>

    <div class="container">
        <h1 class="text-center mt-5">Edit ticket</h1>

        <form action="/ajax/ticket/update" method="post" id="edit-ticket-form">
            <input type="hidden" name="id" id="id" value="<?= $ticket->getId() ?>">

            <div id="error-alert" class="alert alert-danger d-none" role="alert"></div>
            <div id="success-alert" class="alert alert-success d-none" role="alert"></div>

            <div class="mb-3">
                <label for="title" class="form-label">Title *</label>
                <input type="text" name="title" id="title" class="form-control" value="<?= $ticket->getTitle() ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description *</label>
                <textarea name="description" id="description" class="form-control" required><?= $ticket->getDescription() ?></textarea>
            </div>
            <div class="mb-3">
                <button id="edit-ticket-btn" type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>