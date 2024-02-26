const MOVARY_CLIENT_IDENTIFIER = 'Movary Web';
const button = document.getElementById('createNewUserBtn');

async function submitNewUser() {
    await fetch('/api/create-user', {
        'method': 'POST',
        'headers': {
            'Content-Type': 'application/json',
            'X-Movary-Client': MOVARY_CLIENT_IDENTIFIER
        },
        'body': JSON.stringify({
            "email": document.getElementById('emailInput').value,
            "username": document.getElementById('usernameInput').value,
            "password": document.getElementById('passwordInput').value,
            "repeatPassword": document.getElementById('repeatPasswordInput').value
        }),
    }).then(response => {
        if(response.status === 200) {
            window.location.href = '/';
        } else {
            return response.json();
        }
    }).then(error => {
        document.getElementById('createUserResponse').innerText = error['message'];
        document.getElementById('createUserResponse').classList.remove('d-none');
    });
}