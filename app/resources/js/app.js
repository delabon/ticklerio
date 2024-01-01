import * as bootstrap from 'bootstrap'

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
        formData.append('csrf_token', document.getElementById('csrf_token').value);

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
        formData.append('csrf_token', document.getElementById('csrf_token').value);

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
            formData.append('csrf_token', document.querySelector('[name="csrf-token"]').attributes.content.value);

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