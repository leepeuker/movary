const MOVARY_CLIENT_IDENTIFIER = 'Movary Web';

async function submitCredentials() {
    const urlParams = new URLSearchParams(window.location.search);
    const redirect = urlParams.get('redirect') ?? '/';

    const response = await loginRequest();

    if (response.status === 200) {
        window.location.href = redirect
        return;
    }

    const forbiddenPageAlert = document.getElementById('forbiddenPageAlert');
    if (forbiddenPageAlert) {
        forbiddenPageAlert.classList.add('d-none');
    }

    if (response.status === 400) {
        const error = await response.json();

        if (error['error'] === 'MissingTotpCode') {
            document.getElementById('loginForm').classList.add('d-none');
            document.getElementById('totpForm').classList.remove('d-none');
            return
        }

        addAlert('loginErrors', error['message'], 'danger', false);
        return;
    }

    if (response.status === 401) {
        const error = await response.json();

        if (error['error'] === 'InvalidTotpCode') {
            addAlert('totpErrors', error['message'], 'danger', false);
            return
        }

        if (error['error'] === 'InvalidCredentials') {
            addAlert('loginErrors', error['message'], 'danger', false);
            return
        }
    }

    addAlert('loginErrors', 'Unexpected server error', 'danger', false);
}

function loginRequest() {
    return fetch('/api/authentication/token', {
        method: 'POST',
        headers: {
            'Content-type': 'application/json',
            'X-Movary-Client': MOVARY_CLIENT_IDENTIFIER
        },
        body: JSON.stringify({
            'email': document.getElementById('email').value,
            'password': document.getElementById('password').value,
            'rememberMe': document.getElementById('rememberMe').checked,
            'totpCode': document.getElementById('totpCode').value,
        })
    });
}

function submitCredentialsOnEnter(event) {
    if (event.keyCode === 13) {
        submitCredentials()
    }
}
