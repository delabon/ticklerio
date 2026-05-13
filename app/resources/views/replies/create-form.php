<?php

/** @var Ticket $ticket */

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;

?>

<?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
    <form id="create-reply-form" class="reply-form-section" action="/ajax/reply/create" method="post">
        <h3><i class="fa-solid fa-reply me-2"></i>Add reply</h3>

        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">

        <textarea name="message" class="form-control" placeholder="Write your reply..."></textarea>

        <button type="submit" class="btn btn-primary mt-3" id="create-reply-btn"><i class="fa-solid fa-paper-plane me-1"></i> Reply</button>
    </form>
<?php endif ?>
