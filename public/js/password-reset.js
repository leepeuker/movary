document.getElementById('updatePassword').addEventListener('click', async () => {
    const newPassword = document.getElementById('newPassword').value

    if (newPassword.length < 8) {
        addAlert('alertPasswordResetDiv', 'Password is too short', 'danger', true, .7)

        return
    }
    if (document.getElementById('repeatPassword').value !== newPassword) {
        addAlert('alertPasswordResetDiv', 'Passwords not matching', 'danger', true, .7)
        return
    }

    const response = await fetch('/password-reset', {
        method: 'post',
        headers: {
            'Content-type': 'application/json',
        },
        body: JSON.stringify({
            'newPassword': newPassword,
            'token': document.getElementById('token').value
        })
    })

    if (response.status === 400) {
        addAlert('alertPasswordResetDiv', await response.text(), 'danger', true, .7)
        return
    }

    if (response.ok === false) {
        addAlert('alertPasswordResetDiv', 'Could not reset password', 'danger', true, .7)
        return
    }

    window.location.href = '/'
});
