document.getElementById('changePasswordUpdateButton').addEventListener('click', async () => {
    const currentPassword = document.getElementById('currentPassword')
    const newPassword = document.getElementById('newPassword')
    const newPasswordRepeat = document.getElementById('newPasswordRepeat')

    currentPassword.classList.remove('invalid-input');
    newPassword.classList.remove('invalid-input');
    newPasswordRepeat.classList.remove('invalid-input');

    let error = false;

    if (currentPassword.value === '') {
        currentPassword.classList.add('invalid-input');
        error = true;
    }

    if (newPassword.value.length < 8) {
        newPassword.classList.add('invalid-input');
        error = true;
    }

    if (newPassword.value !== newPasswordRepeat.value) {
        newPasswordRepeat.classList.add('invalid-input');
        error = true;
    }

    if (error === true) {
        return
    }

    const response = await fetch('/settings/account/password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'currentPassword': currentPassword.value,
            'newPassword': newPassword.value,
            'newPasswordRepeat': newPasswordRepeat.value,
        })
    })

    if (response.ok === true) {
        addAlert('alertChangePasswordDiv', 'Password was updated.', 'success')

        return
    }

    if (response.status === 400) {
        addAlert('alertChangePasswordDiv', await response.text(), 'danger')

        return
    }

    addAlert('alertChangePasswordDiv', 'Server error. Try again.', 'danger')
});
