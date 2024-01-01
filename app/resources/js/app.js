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
