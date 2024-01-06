<?php

require __DIR__ . '/../parts/header.php';
?>

    <div class="container">
        <h1 class="text-center mt-5">Create a ticket</h1>

        <form action="/ajax/ticket/create" method="post" id="create-ticket-form">
            <div id="error-alert" class="alert alert-danger d-none" role="alert"></div>
            <div id="success-alert" class="alert alert-success d-none" role="alert"></div>

            <div class="mb-3">
                <label for="title" class="form-label">Title *</label>
                <input type="text" name="title" id="title" class="form-control" value="" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description *</label>
                <textarea name="description" id="description" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <button id="create-ticket-btn" type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>

<?php

require __DIR__ . '/../parts/footer.php';
?>