<?php

/** @var Ticket $ticket */

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;

?>

<?php if ($ticket->getStatus() === TicketStatus::Publish->value) : ?>
    <form id="create-reply-form" class="my-5" action="/ajax/reply/create" method="post">
        <h3 class="h5">Add reply</h3>

        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>">

        <textarea name="message" class="form-control"></textarea>

        <button type="submit" class="btn btn-primary mt-3" id="create-reply-btn">Reply</button>
    </form>
<?php endif ?>