const MOVARY_CLIENT_IDENTIFIER = 'Movary Web';

async function submitCredentials() {
    const request = await fetch('/api/authentication/create-token', {
        method: 'POST',
        headers: {
            'Content-type': 'application/json',
            'X-Movary-Client': MOVARY_CLIENT_IDENTIFIER
        },
        body: JSON.stringify({
            'email': document.getElementById('email').value,
            'password': document.getElementById('password').value,
            'rememberMe': document.getElementById('rememberMe').checked,
            'totpCode': document.getElementById('totpCode').value
        })
    }).then((response) => {
        return response;
    });
    
    if(request.redirected === true) {
        window.location.replace(request.url);
    } else {
        await request.json().then(error => {
            if(error['error'] === 'NoVerificationCode') {
               document.getElementById('LoginForm').classList.add('d-none');
               document.getElementById('TotpForm').classList.remove('d-none');
            } else if(error['error'] === 'InvalidTotpCode') {
                addAlert('totpErrors', error['message'], 'danger', false);
            } else {
                addAlert('loginErrors', error['message'], 'danger', false);
            }
        }).catch(error => {
            console.error(error);
        });
    }
}

function submitCredentialsOnEnter(event) {
    if (event.keyCode == 13) {
        submitCredentials()
    }
}
