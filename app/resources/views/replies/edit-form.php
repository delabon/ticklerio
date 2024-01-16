<?php

/** @var Ticket $ticket */

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;

?>

<?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
    <form id="edit-reply-form" class="my-5 d-none" action="/replies/update" method="post">
        <h3 class="h5">Edit reply</h3>

        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">
        <input type="hidden" name="reply_id" value="0">

        <textarea name="message" class="form-control"></textarea>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary" id="update-reply-btn">Update</button>
            <button type="submit" class="btn btn-secondary" id="trigger-cancel-edit-reply-btn">Cancel</button>
        </div>
    </form>
<?php endif ?>