const dateFormatInput = document.getElementById('dateFormatInput')
const nameInput = document.getElementById('nameInput')
const privacyInput = document.getElementById('privacyInput')
const enableAutomaticWatchlistRemovalInput = document.getElementById('enableAutomaticWatchlistRemovalInput')
const countryInput = document.getElementById('countryInput')
const displayCharacterNamesInput = document.getElementById('displayCharacterNamesInput')

document.getElementById('generalAccountUpdateButton').addEventListener('click', async () => {
    nameInput.classList.remove('invalid-input');

    if (nameInput.value.match(/^[a-zA-Z0-9]+$/) === null) {
        nameInput.classList.add('invalid-input');
        addAlert('alertGeneralAccountDiv', 'Username not meeting requirements', 'danger')

        return
    }

    const response = await updateGeneral(dateFormatInput.value,
        nameInput.value,
        privacyInput.value,
        enableAutomaticWatchlistRemovalInput.checked,
        countryInput.value,
        displayCharacterNamesInput.checked
    )

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

function updateGeneral(dateFormat, username, privacyLevel, enableAutomaticWatchlistRemoval, country, displayCharacterNames) {
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
            'country': country,
            'displayCharacterNames': displayCharacterNames,
        })
    })
}

function deleteApiToken() {
    if (confirm('Do you really want to delete the api token?') === false) {
        return
    }

    removeAlert('alertApiTokenDiv')

    deleteApiTokenRequest().then(() => {
        setApiToken('')
        addAlert('alertApiTokenDiv', 'Deleted api token', 'success')
    }).catch((error) => {
        console.log(error)
        addAlert('alertApiTokenDiv', 'Could not delete api token', 'danger')
    })
}

function regenerateApiToken() {
    if (confirm('Do you really want to regenerate the api token?') === false) {
        return
    }

    removeAlert('alertApiTokenDiv')

    regenerateApiTokenRequest().then(response => {
        setApiToken(response.token)
        addAlert('alertApiTokenDiv', 'Generated new api token', 'success')
    }).catch((error) => {
        console.log(error)
        addAlert('alertApiTokenDiv', 'Could not generate api token', 'danger')
    })
}

async function deleteApiTokenRequest() {
    const response = await fetch('/settings/account/general/api-token', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}

async function regenerateApiTokenRequest() {
    const response = await fetch('/settings/account/general/api-token', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
}

function setApiToken(apiToken) {
    document.getElementById('apiToken').value = apiToken

    document.getElementById('deleteApiTokenButton').disabled = apiToken === null || apiToken.length === 0
}

fetch(
    '/settings/account/general/api-token'
).then(async function (response) {
    if (response.status === 200) {
        const responseData = await response.json();

        setApiToken(responseData.token)
        return
    }

    addAlert('alertApiTokenDiv', 'Could not load api token', 'danger')
}).catch(function (error) {
    console.log(error)
    addAlert('alertApiTokenDiv', 'Could not load api token', 'danger')
})
