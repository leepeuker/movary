const dateFormatInput = document.getElementById('dateFormatInput')
const nameInput = document.getElementById('nameInput')
const privacyInput = document.getElementById('privacyInput')
const enableAutomaticWatchlistRemovalInput = document.getElementById('enableAutomaticWatchlistRemovalInput')
const countryInput = document.getElementById('countryInput')
const displayCharacterNamesInput = document.getElementById('displayCharacterNamesInput')
const displayTmdbRatingsInput = document.getElementById('displayTmdbRatingsInput')
const displayImdbRatingsInput = document.getElementById('displayImdbRatingsInput')
const movieTitleDisplayInput = document.getElementById('movieTitleDisplayInput')
const accountEmailSettings = document.getElementById('accountEmailSettings')
const emailInput = document.getElementById('emailInput')
const emailCurrentPasswordInput = document.getElementById('emailCurrentPasswordInput')
const pendingEmailActions = document.getElementById('pendingEmailActions')
const resendPendingEmailButton = document.getElementById('resendPendingEmailButton')
const cancelPendingEmailButton = document.getElementById('cancelPendingEmailButton')

document.getElementById('accountEmailUpdateButton').addEventListener('click', async () => {
    emailInput.classList.remove('invalid-input')
    emailCurrentPasswordInput.classList.remove('invalid-input')

    if (emailInput.checkValidity() === false) {
        emailInput.classList.add('invalid-input')
        addAlert('alertAccountEmailDiv', 'Email address is not valid.', 'danger')
        return
    }
    if (emailCurrentPasswordInput.value === '') {
        emailCurrentPasswordInput.classList.add('invalid-input')
        addAlert('alertAccountEmailDiv', 'You must enter your current password.', 'danger')
        return
    }

    const response = await fetchWithCsrf(APPLICATION_URL + '/settings/account/email', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'email': emailInput.value,
            'currentPassword': emailCurrentPasswordInput.value,
        })
    })

    if (response.ok === false) {
        const message = await response.text()
        if (message === 'Current password wrong.') {
            emailCurrentPasswordInput.classList.add('invalid-input')
        } else {
            emailInput.classList.add('invalid-input')
        }
        addAlert('alertAccountEmailDiv', message || 'Could not update the email address.', 'danger')
        return
    }

    const result = await response.json()
    emailCurrentPasswordInput.value = ''
    if (result.verificationPending === true) {
        pendingEmailActions.classList.remove('d-none')
        resendPendingEmailButton.classList.remove('d-none')
        document.getElementById('pendingEmailStatus').textContent = 'The current login email remains '
            + accountEmailSettings.dataset.currentEmail + ' until ' + emailInput.value + ' is verified.'
        const message = result.verificationRequested === true
            ? 'Verification email was scheduled.'
            : 'Email change remains pending. Use Resend verification to send another email.'
        addAlert('alertAccountEmailDiv', message, 'success')
        return
    }

    accountEmailSettings.dataset.currentEmail = emailInput.value
    document.getElementById('currentEmailAddress').textContent = emailInput.value
    pendingEmailActions.classList.add('d-none')
    addAlert('alertAccountEmailDiv', 'Email address was updated.', 'success')
})

resendPendingEmailButton.addEventListener('click', async () => {
    const response = await fetchWithCsrf(APPLICATION_URL + '/settings/account/email-change/resend', {
        method: 'POST'
    })

    if (response.ok === false) {
        addAlert('alertAccountEmailDiv', await response.text() || 'Could not schedule the verification email.', 'danger')
        return
    }

    addAlert('alertAccountEmailDiv', 'Verification email was scheduled.', 'success')
})

cancelPendingEmailButton.addEventListener('click', async () => {
    const confirmed = await showConfirmationModal({
        title: 'Cancel email change',
        message: 'Cancel this pending email change?',
        confirmLabel: 'Cancel email change',
        confirmClass: 'btn-danger',
    })
    if (confirmed === false) {
        return
    }

    const response = await fetchWithCsrf(APPLICATION_URL + '/settings/account/email-change', {
        method: 'DELETE'
    })
    if (response.ok === false) {
        addAlert('alertAccountEmailDiv', await response.text() || 'Could not cancel the email change.', 'danger')
        return
    }

    emailInput.value = accountEmailSettings.dataset.currentEmail
    pendingEmailActions.classList.add('d-none')
    addAlert('alertAccountEmailDiv', 'Email change was cancelled.', 'success')
})

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
        displayCharacterNamesInput.checked,
        displayTmdbRatingsInput.checked,
        displayImdbRatingsInput.checked,
        movieTitleDisplayInput.value
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

function updateGeneral(dateFormat, username, privacyLevel, enableAutomaticWatchlistRemoval, country, displayCharacterNames, displayTmdbRatings, displayImdbRatings, movieTitleDisplay) {
    return fetchWithCsrf(APPLICATION_URL + '/settings/account', {
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
            'displayTmdbRatings': displayTmdbRatings,
            'displayImdbRatings': displayImdbRatings,
            'movieTitleDisplay': movieTitleDisplay
        })
    })
}
