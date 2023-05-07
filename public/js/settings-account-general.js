const dateFormatInput = document.getElementById('dateFormatInput')
const nameInput = document.getElementById('nameInput')
const privacyInput = document.getElementById('privacyInput')
const enableAutomaticWatchlistRemovalInput = document.getElementById('enableAutomaticWatchlistRemovalInput')

document.getElementById('generalAccountUpdateButton').addEventListener('click', async () => {
    nameInput.classList.remove('invalid-input');

    if (nameInput.value.match(/^[a-zA-Z0-9]+$/) === null) {
        nameInput.classList.add('invalid-input');
        addAlert('alertGeneralAccountDiv', 'Username not meeting requirements', 'danger')

        return
    }

    const response = await updateGeneral(dateFormatInput.value, nameInput.value, privacyInput.value, enableAutomaticWatchlistRemovalInput.checked)

    switch (response.status) {
        case 200:
            addAlert('alertGeneralAccountDiv', 'Update was successful', 'success')

            return
        case 400:
            const errorMessage = await response.text();

            nameInput.classList.add('invalid-input');
            addAlert('alertGeneralAccountDiv', errorMessage, 'danger')

            return
        default:
            addAlert('alertGeneralAccountDiv', 'Unexpected server error', 'danger')
    }
});

function updateGeneral(dateFormat, username, privacyLevel, enableAutomaticWatchlistRemoval) {
    return fetch('/settings/account', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'dateFormat': dateFormat,
            'username': username,
            'privacyLevel': privacyLevel,
            'enableAutomaticWatchlistRemoval': enableAutomaticWatchlistRemoval,
        })
    })
}
