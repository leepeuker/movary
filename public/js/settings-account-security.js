const errorMessageCurrentPasswordIncorrect = 'Current password wrong';
const errorMessageNewPasswordInvalid = 'New password not meeting requirements';

const currentPassword = document.getElementById('currentPassword')
const newPassword = document.getElementById('newPassword')
const newPasswordRepeat = document.getElementById('newPasswordRepeat')
const accountSecuritySettings = document.getElementById('accountSecuritySettings')
const emailInput = document.getElementById('emailInput')
const pendingEmailSummary = document.getElementById('pendingEmailSummary')
const resendPendingEmailButton = document.getElementById('resendPendingEmailButton')
const cancelPendingEmailButton = document.getElementById('cancelPendingEmailButton')

document.getElementById('accountSecurityUpdateButton').addEventListener('click', async () => {
    emailInput.classList.remove('invalid-input')
    currentPassword.classList.remove('invalid-input')
    newPassword.classList.remove('invalid-input')
    newPasswordRepeat.classList.remove('invalid-input')

    const emailChanged = emailInput.value !== accountSecuritySettings.dataset.currentEmail
    const passwordChanged = newPassword.value !== '' || newPasswordRepeat.value !== ''

    if (emailChanged === false && passwordChanged === false) {
        addAlert('alertAccountSecurityDiv', 'There are no changes to save.', 'info')
        return
    }

    if (emailInput.checkValidity() === false) {
        emailInput.classList.add('invalid-input')
        addAlert('alertAccountSecurityDiv', 'Email address is not valid.', 'danger')
        return
    }
    if (currentPassword.value === '') {
        currentPassword.classList.add('invalid-input')
        addAlert('alertAccountSecurityDiv', 'You must enter your current password.', 'danger')
        return
    }

    if (passwordChanged === true) {
        if ([...newPassword.value].length < window.PASSWORD_MIN_LENGTH) {
            newPassword.classList.add('invalid-input')
            addAlert('alertAccountSecurityDiv', errorMessageNewPasswordInvalid, 'danger')
            return
        }
        if (newPassword.value !== newPasswordRepeat.value) {
            newPasswordRepeat.classList.add('invalid-input')
            addAlert('alertAccountSecurityDiv', 'New passwords do not match', 'danger')
            return
        }
    }

    const response = await updateCredentials(passwordChanged)
    if (response.ok === false) {
        const errorMessage = await response.text()
        if (errorMessage.startsWith(errorMessageCurrentPasswordIncorrect)) {
            currentPassword.classList.add('invalid-input')
        } else if (errorMessage === errorMessageNewPasswordInvalid) {
            newPassword.classList.add('invalid-input')
        } else {
            emailInput.classList.add('invalid-input')
        }
        addAlert('alertAccountSecurityDiv', errorMessage || 'Could not update the security settings.', 'danger')
        return
    }

    const result = await response.json()
    if (result.emailChanged === true) {
        updateEmailState(result)
    }
    if (result.passwordChanged === true) {
        window.location.assign(APPLICATION_URL + '/login?password-change=success')
        return
    }

    currentPassword.value = ''
})

function updateCredentials(passwordChanged) {
    return fetchWithCsrf(APPLICATION_URL + '/settings/account/security', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'email': emailInput.value,
            'currentPassword': currentPassword.value,
            'newPassword': passwordChanged === true ? newPassword.value : null,
        })
    })
}

function updateEmailState(result) {
    if (result.verificationPending === true) {
        accountSecuritySettings.dataset.pendingEmail = emailInput.value
        document.getElementById('pendingEmailAddress').textContent = emailInput.value
        pendingEmailSummary.classList.remove('d-none')
        emailInput.value = accountSecuritySettings.dataset.currentEmail
        emailInput.disabled = true
        if (accountSecuritySettings.dataset.emailVerificationEnabled === 'true') {
            resendPendingEmailButton.classList.remove('d-none')
        }

        const message = result.verificationRequested === true
            ? 'Verification email was scheduled.'
            : 'Email change remains pending. Use Resend to send another verification email.'
        addAlert('alertAccountSecurityDiv', message, 'success')
        return
    }

    accountSecuritySettings.dataset.currentEmail = emailInput.value
    accountSecuritySettings.dataset.pendingEmail = ''
    pendingEmailSummary.classList.add('d-none')
    addAlert('alertAccountSecurityDiv', 'Email address was updated.', 'success')
}

resendPendingEmailButton.addEventListener('click', async () => {
    const response = await fetchWithCsrf(APPLICATION_URL + '/settings/account/email-change/resend', {
        method: 'POST'
    })

    if (response.ok === false) {
        addAlert('alertAccountSecurityDiv', await response.text() || 'Could not schedule the verification email.', 'danger')
        return
    }

    addAlert('alertAccountSecurityDiv', 'Verification email was scheduled.', 'success')
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
        addAlert('alertAccountSecurityDiv', await response.text() || 'Could not cancel the email change.', 'danger')
        return
    }

    accountSecuritySettings.dataset.pendingEmail = ''
    pendingEmailSummary.classList.add('d-none')
    emailInput.disabled = false
    addAlert('alertAccountSecurityDiv', 'Email change was cancelled.', 'success')
})

async function showAddTwoFactorAuthenticationModal() {
    const request = await fetchWithCsrf(APPLICATION_URL + '/settings/account/security/create-totp-uri', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    }).catch(function(error) { 
        console.error(error);
        addAlert('twoFactorAuthenticationAlertDiv', 'Something has gone wrong. Check the logs in Movary or the browser console and try again', 'danger');
    });

    if(!request.ok) {
        addAlert('twoFactorAuthenticationAlertDiv', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
    }

    await request.json().then(function(response) {
        let uri = response['uri'];
        document.getElementById('qrcode').innerHTML = '';
        new QRCode(document.getElementById('qrcode'), {
            text: uri,
            width: 256,
            height: 256
        });
        document.getElementById('TOTPSecret').innerText = response['secret'];
        document.getElementById('TOTPInformation').classList.remove('d-none');
    });

    const modal = new bootstrap.Modal(document.getElementById('addTwoFactorAuthenticationModal'));
    modal.show();

}

async function enableTOTP() {
    let input = document.getElementById('authenticationCodeInput').value;
    let onlyNumbersPattern = /^[0-9]{6}$/; // Checks whether the input only has numbers and is exactly 6 characters
    if(onlyNumbersPattern.test(input) === false) {
        addAlert('twoFactorAuthenticationAlertDiv', 'Authentication code not valid', 'danger');
        return false;
    }

    await fetchWithCsrf(APPLICATION_URL + '/settings/account/security/enable-totp', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'input': input,
            'uri': document.getElementById('qrcode').title
        })
    }).then(function(response) {
        if(response.ok) {
            window.location.reload();
        } else if(response.status === 400) {
            addAlert('twoFactorAuthenticationAlertDiv', 'Authentication code not valid', 'danger');
        } else if(400 < response.status < 600) {
            addAlert('twoFactorAuthenticationAlertDiv', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
        }
    }).catch(function(error) {
        addAlert('twoFactorAuthenticationAlertDiv', 'Authentication code not valid', 'danger');
    });
}

async function disableTOTP(button) {
    const confirmed = await showConfirmationModal({
        title: 'Disable two-factor authentication',
        message: 'Are you sure you want to disable two-factor authentication? Your account will no longer require an authentication code when signing in.',
        confirmLabel: 'Disable',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    button.disabled = true

    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/settings/account/security/disable-totp', {
            method: 'POST'
        })

        if (response.ok) {
            window.location.reload();

            return
        }

        addAlert('twoFactorAuthenticationDisableAlertDiv', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
    } catch (error) {
        addAlert('twoFactorAuthenticationDisableAlertDiv', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
    } finally {
        button.disabled = false
    }
}
