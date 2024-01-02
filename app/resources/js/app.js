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
