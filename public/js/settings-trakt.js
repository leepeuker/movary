async function verifyTraktCredentials() {
    const username = document.getElementById('traktUserName').value;
    const clientId = document.getElementById('traktClientId').value;

    if (username == false || clientId == false) {
        addAlertMessage('Username or client id missing.', 'warning')

        return
    }

    alertPlaceholder.innerHTML = ''

    const response = await fetch('/settings/trakt-verify', {
        method: 'post',
        headers: {
            'Content-type': 'application/json',
        }, body: JSON.stringify({
            'username': username,
            'clientId': clientId
        })
    })

    if (response.status === 400) {
        addAlertMessage('Credentials are not valid.', 'danger')

        return
    }

    if (!response.ok) {
        console.log(`Api error on trakt credentials verification with status: ${response.status}`);

        addAlertMessage('Something went wrong...', 'warning')

        return
    }

    addAlertMessage('Credentials are valid.', 'success')
}

const alertPlaceholder = document.getElementById('alerts')
const addAlertMessage = (message, type) => {
    alertPlaceholder.innerHTML = [`<div class="alert alert-${type} alert-dismissible" role="alert">`, `   <div>${message}</div>`, '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>', '</div>'].join('')
}
