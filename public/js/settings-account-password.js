const errorMessageCurrentPasswordIncorrect = 'Current password not correct.';
const errorMessageNewPasswordInvalid = 'New password not meeting requirements.';

const currentPassword = document.getElementById('currentPassword')
const newPassword = document.getElementById('newPassword')
const newPasswordRepeat = document.getElementById('newPasswordRepeat')

document.getElementById('changePasswordUpdateButton').addEventListener('click', async () => {
    currentPassword.classList.remove('invalid-input');
    newPassword.classList.remove('invalid-input');
    newPasswordRepeat.classList.remove('invalid-input');

    if (currentPassword.value === '') {
        currentPassword.classList.add('invalid-input');
        addAlert('alertChangePasswordDiv', errorMessageCurrentPasswordIncorrect, 'danger')
        return
    }

    if (newPassword.value.length < PASSWORD_MIN_LENGTH) {
        newPassword.classList.add('invalid-input');
        addAlert('alertChangePasswordDiv', errorMessageNewPasswordInvalid, 'danger')
        return
    }

    if (newPassword.value !== newPasswordRepeat.value) {
        newPasswordRepeat.classList.add('invalid-input');
        addAlert('alertChangePasswordDiv', 'New passwords do not match.', 'danger')
        return
    }

    const response = await updatePassword(currentPassword.value, newPassword.value);

    switch (response.status) {
        case 200:
            addAlert('alertChangePasswordDiv', 'Password was updated.', 'success')
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
            addAlert('alertChangePasswordDiv', 'Unexpected server error.', 'danger')
    }
});

function updatePassword(currentPassword, newPassword) {
    return fetch('/settings/account/password', {
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
