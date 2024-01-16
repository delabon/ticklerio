import * as bootstrap from 'bootstrap'

const csrfToken = document.querySelector('[name="csrf-token"]').attributes.content.value;

//
// Login
//

const loginBtn = document.getElementById('btn-login');

if (loginBtn) {
    const alert = document.getElementById('login-alert');

    loginBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Reset alert
        alert.classList.add('d-none');
        alert.innerHTML = '';

        // Send form data
        let formData = new FormData();
        formData.append('email', document.getElementById('email').value);
        formData.append('password', document.getElementById('password').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/auth/login', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                location.href = '/';
            } else {
                response.text().then((text) => {
                    alert.classList.remove('d-none');
                    alert.innerHTML = text;
                });
            }
        }).catch((error) => {
            alert.classList.remove('d-none');
            alert.innerHTML = 'Something went wrong. Please try again later.';
        });
    });
}

//
// Register
//

const registerBtn = document.getElementById('register-btn');

if (registerBtn) {
    const alert = document.getElementById('register-alert');

    registerBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Reset alert
        alert.classList.add('d-none');
        alert.innerHTML = '';

        // Send form data
        let formData = new FormData();
        formData.append('email', document.getElementById('email').value);
        formData.append('first_name', document.getElementById('first-name').value);
        formData.append('last_name', document.getElementById('last-name').value);
        formData.append('password', document.getElementById('password').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/register', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                location.href = '/login';
            } else {
                response.text().then((text) => {
                    alert.classList.remove('d-none');
                    alert.innerHTML = text;
                });
            }
        }).catch((error) => {
            alert.classList.remove('d-none');
            alert.innerHTML = 'Something went wrong. Please try again later.';
        });
    });
}

//
// Logout
//

const logoutBtns = document.querySelectorAll('[href="/logout"]');

if (logoutBtns.length > 0) {
    logoutBtns.forEach((logoutBtn) => {
        logoutBtn.addEventListener('click', function (e){
            e.preventDefault();

            // Send form data
            let formData = new FormData();
            formData.append('csrf_token', csrfToken);

            fetch('/ajax/auth/logout', {
                'method': 'POST',
                'body': formData
            }).then((response) => {
                if (response.status === 200) {
                    location.href = '/';
                }
            }).catch((error) => {
                console.log(error);
            });
        });
    });
}

//
// Account (edit)
//

const updateAccountBtn = document.getElementById('update-account-btn');

if (updateAccountBtn) {
    const errorAlert = document.getElementById('account-error-alert');
    const successAlert = document.getElementById('account-success-alert');

    updateAccountBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Reset alerts
        successAlert.classList.add('d-none');
        errorAlert.classList.add('d-none');
        errorAlert.innerHTML = '';

        // Send form data
        let formData = new FormData();
        formData.append('email', document.getElementById('email').value);
        formData.append('first_name', document.getElementById('first-name').value);
        formData.append('last_name', document.getElementById('last-name').value);
        formData.append('password', document.getElementById('password').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/user/update', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                successAlert.classList.remove('d-none');
            } else {
                response.text().then((text) => {
                    errorAlert.classList.remove('d-none');
                    errorAlert.innerHTML = text;
                });
            }
        }).catch((error) => {
            errorAlert.classList.remove('d-none');
            errorAlert.innerHTML = 'Something went wrong. Please try again later.';
        });
    });
}

//
// Send password-reset email
//

const sendPassResetEmailBtn = document.getElementById('send-reset-password-email-btn');

if (sendPassResetEmailBtn) {
    const errorAlert = document.getElementById('error-alert');
    const successAlert = document.getElementById('success-alert');

    sendPassResetEmailBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Reset alerts
        successAlert.classList.add('d-none');
        successAlert.innerHTML = '';
        errorAlert.classList.add('d-none');
        errorAlert.innerHTML = '';

        // Send form data
        let formData = new FormData();
        formData.append('email', document.getElementById('email').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/password-reset/send', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    successAlert.classList.remove('d-none');
                    successAlert.innerHTML = text;
                });

                document.getElementById('email').value = '';
            } else {
                response.text().then((text) => {
                    errorAlert.classList.remove('d-none');
                    errorAlert.innerHTML = text;
                });
            }
        }).catch((error) => {
            errorAlert.classList.remove('d-none');
            errorAlert.innerHTML = 'Something went wrong. Please try again later.';
        });
    });
}

//
// Reset password
//

const resetPasswordBtn = document.getElementById('reset-password-btn');

if (resetPasswordBtn) {
    const errorAlert = document.getElementById('error-alert');
    const successAlert = document.getElementById('success-alert');

    resetPasswordBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Reset alerts
        successAlert.classList.add('d-none');
        successAlert.innerHTML = '';
        errorAlert.classList.add('d-none');
        errorAlert.innerHTML = '';

        const $password = document.getElementById('new_password');
        const $passwordConfirm = document.getElementById('password_match');

        if ($password.value !== $passwordConfirm.value) {
            errorAlert.classList.remove('d-none');
            errorAlert.innerHTML = 'Passwords do not match.';
            return;
        }

        // Send form data
        let formData = new FormData();
        formData.append('reset_password_token', document.getElementById('reset_password_token').value);
        formData.append('new_password', $password.value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/password-reset/reset', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    successAlert.classList.remove('d-none');
                    successAlert.innerHTML = text + ' You can now <a href="/login">login</a>.';
                });

                $password.value = '';
                $passwordConfirm.value = '';
            } else {
                response.text().then((text) => {
                    errorAlert.classList.remove('d-none');
                    errorAlert.innerHTML = text;
                });
            }
        }).catch((error) => {
            errorAlert.classList.remove('d-none');
            errorAlert.innerHTML = 'Something went wrong. Please try again later.';
        });
    });
}

//
// Create ticket
//

const createTicketBtn = document.getElementById('create-ticket-btn');

if (createTicketBtn) {
    const errorAlert = document.getElementById('error-alert');
    const successAlert = document.getElementById('success-alert');

    createTicketBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Reset alerts
        successAlert.classList.add('d-none');
        successAlert.innerHTML = '';
        errorAlert.classList.add('d-none');
        errorAlert.innerHTML = '';

        // Send form data
        let formData = new FormData();
        formData.append('title', document.getElementById('title').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/ticket/store', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.json().then((jsonData) => {
                    successAlert.classList.remove('d-none');
                    successAlert.innerHTML = jsonData.message + ' You can now <a href="/tickets/' + jsonData.id + '">view your ticket</a>.';
                });

                document.getElementById('title').value = '';
                document.getElementById('description').value = '';
            } else {
                response.text().then((text) => {
                    errorAlert.classList.remove('d-none');
                    errorAlert.innerHTML = text;
                });
            }
        }).catch((error) => {
            errorAlert.classList.remove('d-none');
            errorAlert.innerHTML = 'Something went wrong. Please try again later.';
        });
    });
}

//
// Edit ticket
//

const editTicketBtn = document.getElementById('edit-ticket-btn');

if (editTicketBtn) {
    const errorAlert = document.getElementById('error-alert');
    const successAlert = document.getElementById('success-alert');

    editTicketBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Reset alerts
        successAlert.classList.add('d-none');
        successAlert.innerHTML = '';
        errorAlert.classList.add('d-none');
        errorAlert.innerHTML = '';

        // Send form data
        let formData = new FormData();
        formData.append('title', document.getElementById('title').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('id', document.getElementById('id').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/ticket/update', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    successAlert.classList.remove('d-none');
                    successAlert.innerHTML = text + ' You can now <a href="/tickets/' + document.getElementById('id').value + '">view your ticket</a>.';
                });
            } else {
                response.text().then((text) => {
                    errorAlert.classList.remove('d-none');
                    errorAlert.innerHTML = text;
                });
            }
        }).catch((error) => {
            errorAlert.classList.remove('d-none');
            errorAlert.innerHTML = 'Something went wrong. Please try again later.';
        });
    });
}

//
// Delete ticket
//

const deleteTicketBtn = document.getElementById('delete-ticket-btn');

if (deleteTicketBtn) {
    deleteTicketBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Send form data
        let formData = new FormData();
        formData.append('id', deleteTicketBtn.dataset.id);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/ticket/delete', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    alert(text);
                    location.href = '/tickets';
                });
            } else {
                response.text().then((text) => {
                    alert(text);
                });
            }
        }).catch((error) => {
            alert('Something went wrong. Please try again later.');
        });
    });
}

//
// Change ticket status by admin
//

const ticketStatusDropdown = document.getElementById('ticket-status');

if (ticketStatusDropdown) {
    ticketStatusDropdown.addEventListener('change', function (){
        // Send form data
        let formData = new FormData();
        formData.append('id', ticketStatusDropdown.dataset.id);
        formData.append('status', ticketStatusDropdown.value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/ticket/status/update', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            response.text().then((text) => {
                alert(text);
            });

            if (response.status === 200) {
                location.reload();
            }
        }).catch((error) => {
            alert('Something went wrong. Please try again later.');
        });
    });
}

//
// Ban user by admin
//

const banUserBtn = document.getElementById('ban-user-btn');

if (banUserBtn) {
    banUserBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Send form data
        let formData = new FormData();
        formData.append('id', banUserBtn.dataset.id);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/user/ban', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    alert(text);
                    location.reload();
                });
            } else {
                response.text().then((text) => {
                    alert(text);
                });
            }
        }).catch((error) => {
            alert('Something went wrong. Please try again later.');
        });
    });
}

//
// Unban user by admin
//

const unbanUserBtn = document.getElementById('unban-user-btn');

if (unbanUserBtn) {
    unbanUserBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Send form data
        let formData = new FormData();
        formData.append('id', unbanUserBtn.dataset.id);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/user/unban', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    alert(text);
                    location.reload();
                });
            } else {
                response.text().then((text) => {
                    alert(text);
                });
            }
        }).catch((error) => {
            alert('Something went wrong. Please try again later.');
        });
    });
}

//
// Delete user account by themselves
//

const deleteUserBtn = document.getElementById('delete-user-btn');

if (deleteUserBtn) {
    deleteUserBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Send form data
        let formData = new FormData();
        formData.append('id', deleteUserBtn.dataset.id);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/user/delete', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    alert(text);
                    location.reload();
                });
            } else {
                response.text().then((text) => {
                    alert(text);
                });
            }
        }).catch((error) => {
            alert('Something went wrong. Please try again later.');
        });
    });
}

//
// Create reply
//

const createReplyBtn = document.getElementById('create-reply-btn');

if (createReplyBtn) {
    createReplyBtn.addEventListener('click', function (e){
        e.preventDefault();

        // Send form data
        let formData = new FormData();
        formData.append('ticket_id', document.querySelector('#create-reply-form [name="ticket_id"]').value);
        formData.append('message', document.querySelector('#create-reply-form [name="message"]').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/reply/create', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    alert(text);
                    location.href = location.href.split('?')[0].split('#')[0];
                });
            } else {
                response.text().then((text) => {
                    alert(text);
                });
            }
        }).catch((error) => {
            alert('Something went wrong. Please try again later.');
        });
    });
}

//
// Trigger edit reply
//

const triggerEditReplyBtns = document.querySelectorAll('.trigger-edit-reply-btn');

if (triggerEditReplyBtns.length > 0) {
    triggerEditReplyBtns.forEach((triggerEditReplyBtn) => {
        triggerEditReplyBtn.addEventListener('click', function (e){
            e.preventDefault();

            const replyId = triggerEditReplyBtn.dataset.id;
            const $editForm = document.getElementById('edit-reply-form');
            const $createForm = document.getElementById('create-reply-form');

            $createForm.classList.add('d-none');
            $editForm.classList.remove('d-none');
            $editForm.querySelector('[name="reply_id"]').value = replyId;
            $editForm.querySelector('[name="message"]').value = document.querySelector('.reply-card[data-id="' + replyId + '"] .reply-message').innerHTML;

            location.href = "#edit-reply-form";
        });
    });
}

//
// Trigger cancel edit reply
//

const triggerCancelEditReplyBtn = document.getElementById('trigger-cancel-edit-reply-btn');

if (triggerCancelEditReplyBtn) {
    triggerCancelEditReplyBtn.addEventListener('click', function (e){
        e.preventDefault();

        const $editForm = document.getElementById('edit-reply-form');
        const $createForm = document.getElementById('create-reply-form');

        $createForm.classList.remove('d-none');
        $editForm.classList.add('d-none');
        $editForm.querySelector('[name="reply_id"]').value = 0;
        $editForm.querySelector('[name="message"]').value = '';

        location.href = "#create-reply-form";
    });
}

//
// Update reply
//

const updateReplyBtn = document.getElementById('update-reply-btn');

if (updateReplyBtn) {
    updateReplyBtn.addEventListener('click', function (e){
        e.preventDefault();

        const id = document.querySelector('#edit-reply-form [name="reply_id"]').value;

        // Send form data
        let formData = new FormData();
        formData.append('id', id);
        formData.append('ticket_id', document.querySelector('#edit-reply-form [name="ticket_id"]').value);
        formData.append('message', document.querySelector('#edit-reply-form [name="message"]').value);
        formData.append('csrf_token', csrfToken);

        fetch('/ajax/reply/update', {
            'method': 'POST',
            'body': formData
        }).then((response) => {
            if (response.status === 200) {
                response.text().then((text) => {
                    alert(text);
                    location.reload();
                });
            } else {
                response.text().then((text) => {
                    alert(text);
                });
            }
        }).catch((error) => {
            alert('Something went wrong. Please try again later.');
        });
    });
}

//
// Delete reply
//

const deleteReplyBtns = document.querySelectorAll('.delete-reply-btn');

if (deleteReplyBtns.length > 0) {
    deleteReplyBtns.forEach((deleteReplyBtn) => {
        deleteReplyBtn.addEventListener('click', function (e){
            e.preventDefault();

            // Send form data
            let formData = new FormData();
            formData.append('id', deleteReplyBtn.dataset.id);
            formData.append('csrf_token', csrfToken);

            fetch('/ajax/reply/delete', {
                'method': 'POST',
                'body': formData
            }).then((response) => {
                if (response.status === 200) {
                    response.text().then((text) => {
                        alert(text);
                        location.href = location.href.split('?')[0].split('#')[0];
                    });
                } else {
                    response.text().then((text) => {
                        alert(text);
                    });
                }
            }).catch((error) => {
                alert('Something went wrong. Please try again later.');
            });
        });
    });
}
