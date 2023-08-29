const userModal = new bootstrap.Modal('#userModal', {keyboard: false})
const passwordResetModal = new bootstrap.Modal('#passwordResetModal', {keyboard: false})

const userTable = document.getElementById('usersTable');
const userTableRows = userTable.getElementsByTagName('tr');
const passwordResetTable = document.getElementById('passwordResetTable');
const passwordResetTableRows = passwordResetTable.getElementsByTagName('tr');

reloadUserTable()
reloadPasswordResetTable()

function registerUserTableRowClickEvent() {
    for (let i = 0; i < userTableRows.length; i++) {
        if (i === 0) continue

        userTableRows[i].onclick = function () {
            prepareEditUserModal(
                this.cells[0].innerHTML,
                this.cells[1].innerHTML,
                this.cells[2].innerHTML,
                this.cells[3].innerHTML === '1'
            )

            userModal.show()
        };
    }
}

function registerPasswordResetTableRowClickEvent() {
    for (let i = 0; i < passwordResetTableRows.length; i++) {
        if (i === 0) continue

        passwordResetTableRows[i].onclick = function () {
            prepareEditPasswordResetModal(
                this.cells[0].innerHTML,
                this.cells[1].innerHTML,
                this.cells[2].innerHTML,
            )

            passwordResetModal.show()
        };
    }
}

function showCreateUserModal() {
    prepareCreateUserModal()
    userModal.show()
}

function showCreatePasswordResetModal() {
    prepareCreatePasswordResetModal()
    passwordResetModal.show()
}

function prepareCreateUserModal() {
    document.getElementById('userModalHeaderTitle').innerHTML = 'Create User'

    document.getElementById('userModalPasswordInput').required = true
    document.getElementById('userModalRepeatPasswordInput').required = false

    document.getElementById('userModalPasswordInputRequiredStar').classList.remove('d-none')
    document.getElementById('userModalRepeatPasswordInputRequiredStar').classList.remove('d-none')
    document.getElementById('userModalFooterCreateButton').classList.remove('d-none')
    document.getElementById('userModalFooterButtons').classList.add('d-none')

    document.getElementById('userModalIdInput').value = ''
    document.getElementById('userModalNameInput').value = ''
    document.getElementById('userModalEmailInput').value = ''
    document.getElementById('userModalPasswordInput').value = ''
    document.getElementById('userModalRepeatPasswordInput').value = ''
    document.getElementById('userModalIsAdminInput').checked = ''

    document.getElementById('userModalAlerts').innerHTML = ''

    // Remove class invalid-input from all (input) elements
    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

function prepareCreatePasswordResetModal() {
    document.getElementById('userModalIdInput').value = ''
    document.getElementById('userModalNameInput').value = ''

    document.getElementById('passwordResetModalUserIdSelect').disabled = false
    document.getElementById('passwordResetExpirationDateDiv').classList.add('d-none')
    document.getElementById('passwordResetExpirationHoursDiv').classList.remove('d-none')
    document.getElementById('passwordResetTokenDiv').classList.add('d-none')
    document.getElementById('passwordResetUrlDiv').classList.add('d-none')

    document.getElementById('createPasswordResetButton').classList.remove('d-none')
    document.getElementById('deletePasswordResetButton').classList.add('d-none')
    document.getElementById('emailPasswordResetButton').classList.add('d-none')
    document.getElementById('copyPasswordResetButton').classList.add('d-none')

    removeAlert('passwordResetModalAlerts')

    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

function prepareEditUserModal(id, name, email, isAdmin) {
    document.getElementById('userModalHeaderTitle').innerHTML = 'Edit User'

    document.getElementById('userModalPasswordInput').required = false
    document.getElementById('userModalRepeatPasswordInput').required = false

    document.getElementById('userModalPasswordInputRequiredStar').classList.add('d-none')
    document.getElementById('userModalRepeatPasswordInputRequiredStar').classList.add('d-none')
    document.getElementById('userModalFooterCreateButton').classList.add('d-none')
    document.getElementById('userModalFooterButtons').classList.remove('d-none')

    document.getElementById('userModalIdInput').value = id
    document.getElementById('userModalNameInput').value = name
    document.getElementById('userModalEmailInput').value = email
    document.getElementById('userModalIsAdminInput').checked = isAdmin
    document.getElementById('userModalPasswordInput').value = ''
    document.getElementById('userModalRepeatPasswordInput').value = ''

    document.getElementById('userModalAlerts').innerHTML = ''

    // Remove class invalid-input from all (input) elements
    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

function prepareEditPasswordResetModal(userId, token, expirationDate) {
    document.getElementById('passwordResetModalUserIdSelect').value = userId
    document.getElementById('passwordResetToken').value = token
    document.getElementById('expirationDate').value = expirationDate
    document.getElementById('passwordResetUrl').value = document.getElementById('applicationUrl').value + 'password-reset/' + token

    document.getElementById('passwordResetModalUserIdSelect').disabled = true
    document.getElementById('passwordResetExpirationDateDiv').classList.remove('d-none')
    document.getElementById('passwordResetExpirationHoursDiv').classList.add('d-none')
    document.getElementById('passwordResetTokenDiv').classList.remove('d-none')
    document.getElementById('passwordResetUrlDiv').classList.remove('d-none')

    document.getElementById('createPasswordResetButton').classList.add('d-none')
    document.getElementById('deletePasswordResetButton').classList.remove('d-none')
    document.getElementById('emailPasswordResetButton').classList.remove('d-none')
    document.getElementById('copyPasswordResetButton').classList.remove('d-none')

    console.log(document.getElementById('emailEnabled').value)
    document.getElementById('emailPasswordResetButton').disabled = document.getElementById('emailEnabled').value !== '1'
    document.getElementById('copyPasswordResetButton').disabled = false
    document.getElementById('deletePasswordResetButton').disabled = false
    document.getElementById('passwordResetModalLoadingSpinner').classList.add('remove')
    removeAlert('passwordResetModalAlerts')

    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

function validateCreateUserInput() {
    let error = false

    const nameInput = document.getElementById('userModalNameInput');
    const passwordInput = document.getElementById('userModalPasswordInput');
    const passwordRepeatInput = document.getElementById('userModalRepeatPasswordInput');
    const emailInput = document.getElementById('userModalEmailInput');

    let mustNotBeEmptyInputs = [nameInput, emailInput]

    if (passwordInput.required === true) {
        mustNotBeEmptyInputs.push(passwordInput, passwordRepeatInput)
    }

    mustNotBeEmptyInputs.forEach((input) => {
        input.classList.remove('invalid-input');
        if (input.value.toString() === '') {
            input.classList.add('invalid-input');

            error = true
        }
    })

    if (passwordInput.required === true || passwordInput.value.length > 0) {
        if (passwordInput.value.length < PASSWORD_MIN_LENGTH || passwordInput.value !== passwordRepeatInput.value) {
            if (passwordInput.value.length < PASSWORD_MIN_LENGTH) {
                passwordInput.classList.add('invalid-input');
            }
            passwordRepeatInput.classList.add('invalid-input');

            error = true
        }
    }

    if (emailInput.value.includes('@') === false) {
        emailInput.classList.add('invalid-input');

        error = true
    }

    if (nameInput.value.match(/^[a-zA-Z0-9]+$/) === null) {
        nameInput.classList.add('invalid-input');

        error = true
    }

    return error
}

function validateCreatePasswordResetInput() {
    let error = false

    const passwordResetModalUserIdSelect = document.getElementById('passwordResetModalUserIdSelect');
    const expirationInHours = document.getElementById('expirationInHours');

    let mustNotBeEmptyInputs = [passwordResetModalUserIdSelect, expirationInHours]

    mustNotBeEmptyInputs.forEach((input) => {
        input.classList.remove('invalid-input');
        if (input.value.toString() === '') {
            input.classList.add('invalid-input');

            error = true
        }
    })

    if (/^\d+$/.test(expirationInHours.value) === false) {
        expirationInHours.classList.add('invalid-input');
        error = true
    }

    return error
}

document.getElementById('createUserButton').addEventListener('click', async () => {
    if (validateCreateUserInput() === true) {
        return;
    }

    const response = await fetch('/api/users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'name': document.getElementById('userModalNameInput').value,
            'password': document.getElementById('userModalPasswordInput').value,
            'email': document.getElementById('userModalEmailInput').value,
            'isAdmin': document.getElementById('userModalIsAdminInput').checked,
        })
    })

    if (response.status !== 200) {
        setUserModalAlertServerError(await response.text())
        return
    }

    setUserManagementAlert('User was created: ' + document.getElementById('userModalNameInput').value)

    reloadUserTable()
    userModal.hide()
})

document.getElementById('createPasswordResetButton').addEventListener('click', async () => {
    if (validateCreatePasswordResetInput() === true) {
        return;
    }

    const response = await fetch('/settings/server/users/password-reset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'userId': document.getElementById('passwordResetModalUserIdSelect').value,
            'expirationInHours': document.getElementById('expirationInHours').value,
        })
    })

    if (response.status !== 200) {
        addAlert('passwordResetAlerts', 'Could not create password reset', 'danger')
        return
    } else {
        addAlert('passwordResetAlerts', 'Created password reset', 'success')
    }

    reloadPasswordResetTable()
    passwordResetModal.hide()
})

function setUserModalAlertServerError(message = "Server error, please try again.") {
    document.getElementById('userModalAlerts').innerHTML = '<div class="alert alert-danger alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
}

document.getElementById('updateUserButton').addEventListener('click', async () => {
    if (validateCreateUserInput() === true) {
        return;
    }

    let password = document.getElementById('userModalPasswordInput').value;
    if (password === '') {
        password = null
    }

    const response = await fetch('/api/users/' + document.getElementById('userModalIdInput').value, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'name': document.getElementById('userModalNameInput').value,
            'email': document.getElementById('userModalEmailInput').value,
            'isAdmin': document.getElementById('userModalIsAdminInput').checked,
            'password': password,
        })
    })

    if (response.status !== 200) {
        setUserModalAlertServerError(await response.text())

        return
    }

    setUserManagementAlert('User was updated: ' + document.getElementById('userModalNameInput').value)

    reloadUserTable()
    userModal.hide()
})

document.getElementById('deleteUserButton').addEventListener('click', async () => {
    removeAlert('passwordResetModalAlerts')

    if (confirm('Are you sure you want to delete the user?') === false) {
        return
    }

    const response = await fetch('/api/users/' + document.getElementById('userModalIdInput').value, {
        method: 'DELETE'
    });

    if (response.status !== 200) {
        setUserModalAlertServerError()
        return
    }

    setUserManagementAlert('User was deleted: ' + document.getElementById('userModalNameInput').value)

    reloadUserTable()
    userModal.hide()
})

document.getElementById('deletePasswordResetButton').addEventListener('click', async () => {
    document.getElementById('emailPasswordResetButton').disabled = true
    document.getElementById('copyPasswordResetButton').disabled = true
    document.getElementById('deletePasswordResetButton').disabled = true
    document.getElementById('passwordResetModalLoadingSpinner').classList.add('remove')
    removeAlert('passwordResetModalAlerts')

    if (confirm('Are you sure you want to delete the password reset?') === false) {
        if (document.getElementById('emailEnabled').value === '1') {
            document.getElementById('emailPasswordResetButton').disabled = false
        }
        document.getElementById('copyPasswordResetButton').disabled = false
        document.getElementById('deletePasswordResetButton').disabled = false
        document.getElementById('passwordResetModalLoadingSpinner').classList.add('d-none')

        return
    }

    const response = await fetch('/settings/server/users/password-reset/' + document.getElementById('passwordResetToken').value, {
        method: 'DELETE'
    });

    if (document.getElementById('emailEnabled').value === '1') {
        document.getElementById('emailPasswordResetButton').disabled = false
    }
    document.getElementById('copyPasswordResetButton').disabled = false
    document.getElementById('deletePasswordResetButton').disabled = false
    document.getElementById('passwordResetModalLoadingSpinner').classList.add('d-none')

    if (response.status !== 200) {
        addAlert('passwordResetModalAlerts', 'Could not delete password reset', 'danger')

        return
    }

    addAlert('passwordResetAlerts', 'Password reset was deleted', 'success')

    reloadPasswordResetTable()
    passwordResetModal.hide()
})
document.getElementById('copyPasswordResetButton').addEventListener('click', async () => {
    navigator.clipboard.writeText(document.getElementById('passwordResetUrl').value);
    removeAlert('passwordResetModalAlerts')

    addAlert('passwordResetModalAlerts', 'Copied url to clipboard', 'success')

})

document.getElementById('emailPasswordResetButton').addEventListener('click', async () => {
    document.getElementById('emailPasswordResetButton').disabled = true
    document.getElementById('copyPasswordResetButton').disabled = true
    document.getElementById('deletePasswordResetButton').disabled = true
    document.getElementById('passwordResetModalLoadingSpinner').classList.remove('d-none')
    removeAlert('passwordResetModalAlerts')

    const response = await fetch('/settings/server/users/password-reset/' + document.getElementById('passwordResetToken').value + '/send-email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
    })

    if (document.getElementById('emailEnabled').value === '1') {
        document.getElementById('emailPasswordResetButton').disabled = false
    }
    document.getElementById('copyPasswordResetButton').disabled = false
    document.getElementById('deletePasswordResetButton').disabled = false
    document.getElementById('passwordResetModalLoadingSpinner').classList.add('d-none')

    if (response.status !== 200) {
        addAlert('passwordResetModalAlerts', 'Email sending failed', 'danger')

        return
    }

    addAlert('passwordResetModalAlerts', 'Email successfully sent', 'success')
})

function setUserManagementAlert(message, type = 'success') {
    const userManagementAlerts = document.getElementById('userManagementAlerts');
    userManagementAlerts.classList.remove('d-none')
    userManagementAlerts.innerHTML = ''
    userManagementAlerts.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
    userManagementAlerts.style.textAlign = 'center'
}

async function reloadUserTable() {
    userTable.getElementsByTagName('tbody')[0].innerHTML = ''
    document.getElementById('userTableLoadingSpinner').classList.remove('d-none')

    const response = await fetch('/api/users');

    if (response.status !== 200) {
        setUserManagementAlert('Could not load users', 'danger')
        document.getElementById('userTableLoadingSpinner').classList.add('d-none')

        return
    }

    const users = await response.json();

    document.getElementById('userTableLoadingSpinner').classList.add('d-none')

    users.forEach((user) => {
        let row = document.createElement('tr');
        row.innerHTML = '<td>' + user.id + '</td>';
        row.innerHTML += '<td>' + user.name + '</td>';
        row.innerHTML += '<td>' + user.email + '</td>';
        row.innerHTML += '<td>' + user.isAdmin + '</td>';
        row.style.cursor = 'pointer'

        userTable.getElementsByTagName('tbody')[0].appendChild(row);
    })

    registerUserTableRowClickEvent()
}

async function reloadPasswordResetTable() {
    passwordResetTable.getElementsByTagName('tbody')[0].innerHTML = ''
    document.getElementById('passwordResetTableLoadingSpinner').classList.remove('d-none')

    const response = await fetch('/settings/server/users/password-reset');

    if (response.status !== 200) {
        addAlert('passwordResetAlerts', 'Could not load password resets', 'danger')
        document.getElementById('passwordResetTableLoadingSpinner').classList.add('d-none')

        return
    }

    const passwordResets = await response.json();

    document.getElementById('passwordResetTableLoadingSpinner').classList.add('d-none')

    passwordResets.forEach((passwordReset) => {
        let row = document.createElement('tr');
        row.innerHTML = '<td>' + passwordReset.user_id + '</td>';
        row.innerHTML += '<td>' + passwordReset.token + '</td>';
        row.innerHTML += '<td>' + passwordReset.expires_at + '</td>';
        row.style.cursor = 'pointer'

        passwordResetTable.getElementsByTagName('tbody')[0].appendChild(row);
    })

    registerPasswordResetTableRowClickEvent()
}
