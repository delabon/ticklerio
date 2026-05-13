<?php

/** @var Ticket $ticket */

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;

?>

<?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
    <form id="edit-reply-form" class="reply-form-section d-none" action="/replies/update" method="post">
        <h3><i class="fa-solid fa-pen me-2"></i>Edit reply</h3>

        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">
        <input type="hidden" name="reply_id" value="0">

        <textarea name="message" class="form-control"></textarea>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="update-reply-btn"><i class="fa-solid fa-check me-1"></i> Update</button>
            <button type="submit" class="btn btn-secondary" id="trigger-cancel-edit-reply-btn"><i class="fa-solid fa-xmark me-1"></i> Cancel</button>
        </div>
    </form>
<?php endif ?>
