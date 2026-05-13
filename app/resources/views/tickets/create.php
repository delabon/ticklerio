<?php

require __DIR__ . '/../parts/header.php';
?>

    <div class="form-page">
        <div class="form-card">
            <h1><i class="fa-solid fa-plus-circle me-2"></i>Create a ticket</h1>

            <form action="/ajax/ticket/store" method="post" id="create-ticket-form">
                <div id="error-alert" class="alert alert-danger d-none" role="alert"></div>
                <div id="success-alert" class="alert alert-success d-none" role="alert"></div>

                <div class="mb-3">
                    <label for="title" class="form-label">Title *</label>
                    <input type="text" name="title" id="title" class="form-control" value="" placeholder="Brief summary of your issue" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description *</label>
                    <textarea name="description" id="description" class="form-control" placeholder="Describe your issue in detail..." required></textarea>
                </div>
                <div class="mb-3">
                    <button id="create-ticket-btn" type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> Create Ticket</button>
                </div>
            </form>
        </div>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>
