async function verifyTraktCredentials() {
    const username = document.getElementById('traktUserName').value;
    const clientId = document.getElementById('traktClientId').value;

    if (username == false || clientId == false) {
        addAlertMessage('Username or client id missing', 'warning')

        return
    }

    document.getElementById('verifyButton').disabled = true;
    alertPlaceholder.innerHTML = ''

    const response = await fetch('/settings/trakt/verify-credentials', {
        method: 'post',
        headers: {
            'Content-type': 'application/json',
        },
        body: JSON.stringify({
            'username': username,
            'clientId': clientId
        })
    })

    if (response.ok) {
        addAlertMessage('Credentials are valid', 'success')
    } else if (response.status === 400) {
        addAlertMessage('Credentials are not valid', 'danger')
    } else {
        addAlertMessage('Something went wrong...', 'warning')

        console.log(`Api error on trakt credentials verification with status: ${response.status}`);
    }

    document.getElementById('verifyButton').disabled = false;
}

const alertPlaceholder = document.getElementById('alerts')
const addAlertMessage = (message, type) => {
    alertPlaceholder.innerHTML = [`<div class="alert alert-${type} alert-dismissible" role="alert">`, `   <div>${message}</div>`, '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>', '</div>'].join('')
}
