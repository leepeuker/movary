const errorMessageCurrentPasswordIncorrect = 'Current password wrong';
const errorMessageNewPasswordInvalid = 'New password not meeting requirements';

const currentPassword = document.getElementById('currentPassword')
const newPassword = document.getElementById('newPassword')
const newPasswordRepeat = document.getElementById('newPasswordRepeat')

document.getElementById('changePasswordUpdateButton').addEventListener('click', async () => {
    currentPassword.classList.remove('invalid-input');
    newPassword.classList.remove('invalid-input');
    newPasswordRepeat.classList.remove('invalid-input');

    if (currentPassword.value === '') {
        currentPassword.classList.add('invalid-input');
        addAlert('alertChangePasswordDiv', 'You must enter current password', 'danger')
        return
    }

    if (newPassword.value.length < PASSWORD_MIN_LENGTH) {
        newPassword.classList.add('invalid-input');
        addAlert('alertChangePasswordDiv', errorMessageNewPasswordInvalid, 'danger')
        return
    }

    if (newPassword.value !== newPasswordRepeat.value) {
        newPasswordRepeat.classList.add('invalid-input');
        addAlert('alertChangePasswordDiv', 'New passwords do not match', 'danger')
        return
    }

    const response = await updatePassword(currentPassword.value, newPassword.value);

    switch (response.status) {
        case 200:
            addAlert('alertChangePasswordDiv', 'Password was updated', 'success')
            currentPassword.value = ''
            newPassword.value = ''
            newPasswordRepeat.value = ''

            return
        case 400:
            const errorMessage = await response.text();

            if (errorMessage === errorMessageCurrentPasswordIncorrect) {
                currentPassword.classList.add('invalid-input');
            }

            if (errorMessage === errorMessageNewPasswordInvalid) {
                newPassword.classList.add('invalid-input');
            }

            addAlert('alertChangePasswordDiv', errorMessage, 'danger')

            return
        default:
            addAlert('alertChangePasswordDiv', 'Unexpected server error', 'danger')
    }
});

function updatePassword(currentPassword, newPassword) {
    return fetch('/settings/account/security/update-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'currentPassword': currentPassword,
            'newPassword': newPassword,
        })
    })
}

async function showAddTwoFactorAuthenticationModal() {
    const request = await fetch('/settings/account/security/create-totp-uri', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    }).catch(function(error) { 
        console.error(error);
        addAlert('addTwoFactorAuthenticationErrorLog', 'Something has gone wrong. Check the logs in Movary or the browser console and try again', 'danger');
    });

    if(!request.ok) {
        addAlert('addTwoFactorAuthenticationErrorLog', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
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
        addAlert('addTwoFactorAuthenticationErrorLog', 'Input is incorrect, please try again', 'danger');
        return false;
    }

    await fetch('/settings/account/security/enable-totp', {
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
            addAlert('addTwoFactorAuthenticationErrorLog', 'Input is incorrect, please try again', 'danger');
        } else if(400 < response.status < 600) {
            addAlert('addTwoFactorAuthenticationErrorLog', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
        }
    }).catch(function(error) {
        addAlert('addTwoFactorAuthenticationErrorLog', 'Input is incorrect, please try again', 'danger');
    });
}

async function disableTOTP() {
    await fetch('/settings/account/security/disable-totp', {
        method: 'POST'
    }).then(function(response) {
        if(response.ok) {
            window.location.reload();
        } else if(response.status === 400) {
            addAlert('deleteTwoFactorAuthenticationErrorLog', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
        } else if(400 < response.status < 600) {
            addAlert('deleteTwoFactorAuthenticationErrorLog', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
        }
    }).catch(function(error) {
        addAlert('deleteTwoFactorAuthenticationErrorLog', 'Something has gone wrong. Check the logs in Movary and try again', 'danger');
    });
}
