const userModal = new bootstrap.Modal('#userModal', {keyboard: false})

const table = document.getElementById('usersTable');
const rows = table.getElementsByTagName('tr');
const passwordResetsTable = document.getElementById('passwordResetsTable');
const passwordResetUserButton = document.getElementById('passwordResetUserButton');
const revokeAllPasswordResetsButton = document.getElementById('revokeAllPasswordResetsButton');

reloadTable()
if (passwordResetsTable !== null) {
    reloadPasswordResetTable()
}
if (passwordResetUserButton !== null) {
    passwordResetUserButton.addEventListener('click', () => {
        sendPasswordReset(
            document.getElementById('userModalIdInput').value,
            document.getElementById('userModalNameInput').value,
        )
    })
}

function registerTableRowClickEvent() {
    for (let i = 0; i < rows.length; i++) {
        if (i === 0) continue

        rows[i].onclick = function () {
            prepareEditUserModal(
                this.cells[0].innerHTML,
                this.cells[1].innerHTML,
                this.cells[2].innerHTML,
                this.dataset.isAdmin === 'true'
            )

            userModal.show()
        };
    }
}

function showCreateUserModal() {
    prepareCreateUserModal()
    userModal.show()
}

function prepareCreateUserModal(name) {
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

function prepareEditUserModal(id, name, email, isAdmin, password, repeatPassword) {
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

document.getElementById('createUserButton').addEventListener('click', async () => {
    if (validateCreateUserInput() === true) {
        return;
    }

    const response = await fetch(APPLICATION_URL + '/settings/users', {
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

    reloadTable()
    userModal.hide()
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

    const response = await fetch(APPLICATION_URL + '/settings/users/' + document.getElementById('userModalIdInput').value, {
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

    reloadTable()
    userModal.hide()
})

document.getElementById('deleteUserButton').addEventListener('click', async () => {
    if (confirm('Are you sure you want to delete the user?') === false) {
        return
    }

    const response = await fetch(APPLICATION_URL + '/settings/users/' + document.getElementById('userModalIdInput').value, {
        method: 'DELETE'
    });

    if (response.status !== 200) {
        setUserModalAlertServerError()
        return
    }

    setUserManagementAlert('User was deleted: ' + document.getElementById('userModalNameInput').value)

    reloadTable()
    userModal.hide()
})

function setUserManagementAlert(message, type = 'success') {
    const userManagementAlerts = document.getElementById('userManagementAlerts');
    userManagementAlerts.classList.remove('d-none')
    userManagementAlerts.innerHTML = ''
    userManagementAlerts.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
    userManagementAlerts.style.textAlign = 'center'
}

async function reloadTable() {
    table.getElementsByTagName('tbody')[0].innerHTML = ''
    document.getElementById('userTableLoadingSpinner').classList.remove('d-none')

    const response = await fetch(APPLICATION_URL + '/settings/users');

    if (response.status !== 200) {
        setUserManagementAlert('Could not load users', 'danger')
        document.getElementById('userTableLoadingSpinner').classList.add('d-none')

        return
    }

    const users = await response.json();

    document.getElementById('userTableLoadingSpinner').classList.add('d-none')

    users.forEach((user) => {
        let row = document.createElement('tr');
        const isAdmin = user.isAdmin === true || user.isAdmin === 1 || user.isAdmin === '1'
        row.dataset.isAdmin = isAdmin ? 'true' : 'false'
        row.innerHTML = '<td>' + user.id + '</td>';
        row.innerHTML += '<td>' + user.name + '</td>';
        row.innerHTML += '<td>' + user.email + '</td>';
        row.innerHTML += '<td><span class="badge rounded-pill text-bg-' + (isAdmin ? 'light' : 'dark') + '">' + (isAdmin ? 'Admin' : 'User') + '</span></td>';

        row.style.cursor = 'pointer'

        table.getElementsByTagName('tbody')[0].appendChild(row);
    })

    registerTableRowClickEvent()
}

async function sendPasswordReset(userId, userName) {
    if (confirm('Send a new password reset email to ' + userName + '?') === false) {
        return
    }

    const response = await fetch(APPLICATION_URL + '/settings/users/' + userId + '/password-reset', {
        method: 'POST'
    })

    if (response.ok === false) {
        const message = await response.text()
        setUserModalAlertServerError(message || 'Could not schedule password reset email.')
        return
    }

    setUserManagementAlert('Password reset email was scheduled for ' + userName)
    reloadPasswordResetTable()
    userModal.hide()
}

function appendTextCell(row, value) {
    const cell = document.createElement('td')
    cell.textContent = value
    row.appendChild(cell)
}

function setPasswordResetManagementAlert(message, type = 'success') {
    const alerts = document.getElementById('passwordResetManagementAlerts')
    alerts.replaceChildren()

    const alert = document.createElement('div')
    alert.className = 'alert alert-' + type + ' alert-dismissible'
    alert.setAttribute('role', 'alert')
    alert.textContent = message

    const closeButton = document.createElement('button')
    closeButton.type = 'button'
    closeButton.className = 'btn-close'
    closeButton.dataset.bsDismiss = 'alert'
    closeButton.setAttribute('aria-label', 'Close')
    alert.appendChild(closeButton)

    alerts.appendChild(alert)
}

async function reloadPasswordResetTable() {
    const tableBody = passwordResetsTable.getElementsByTagName('tbody')[0]
    const spinner = document.getElementById('passwordResetTableLoadingSpinner')
    tableBody.replaceChildren()
    spinner.classList.remove('d-none')
    revokeAllPasswordResetsButton.disabled = true

    const response = await fetch(APPLICATION_URL + '/settings/password-resets')
    spinner.classList.add('d-none')

    if (response.ok === false) {
        setPasswordResetManagementAlert('Could not load password resets.', 'danger')
        return
    }

    const resets = await response.json()
    revokeAllPasswordResetsButton.disabled = resets.length === 0
    if (resets.length === 0) {
        const row = document.createElement('tr')
        const cell = document.createElement('td')
        cell.colSpan = 5
        cell.className = 'text-muted'
        cell.textContent = 'No active password resets.'
        row.appendChild(cell)
        tableBody.appendChild(row)
        return
    }

    resets.forEach((reset) => {
        const row = document.createElement('tr')
        appendTextCell(row, reset.name)
        appendTextCell(row, reset.email)
        appendTextCell(row, reset.createdAt + ' UTC')
        appendTextCell(row, reset.expirationDate + ' UTC')

        const actionCell = document.createElement('td')
        const revokeButton = document.createElement('button')
        revokeButton.type = 'button'
        revokeButton.className = 'btn btn-sm btn-danger'
        revokeButton.textContent = 'Revoke'
        revokeButton.addEventListener('click', () => revokePasswordReset(reset.userId, reset.name))
        actionCell.appendChild(revokeButton)
        row.appendChild(actionCell)
        tableBody.appendChild(row)
    })
}

async function revokeAllPasswordResets() {
    const modal = bootstrap.Modal.getInstance('#passwordResetsRevokeAllModal')

    revokeAllPasswordResetsButton.disabled = true
    modal.hide()

    const response = await fetch(APPLICATION_URL + '/settings/password-resets', {
        method: 'DELETE'
    })

    if (response.ok === false) {
        setPasswordResetManagementAlert('Could not revoke all password reset links.', 'danger')
        revokeAllPasswordResetsButton.disabled = false
        return
    }

    setPasswordResetManagementAlert('All password reset links were revoked.')
    reloadPasswordResetTable()
}

async function revokePasswordReset(userId, userName) {
    if (confirm('Revoke the password reset for ' + userName + '?') === false) {
        return
    }

    const response = await fetch(APPLICATION_URL + '/settings/users/' + userId + '/password-reset', {
        method: 'DELETE'
    })

    if (response.ok === false) {
        setPasswordResetManagementAlert('Could not revoke password reset.', 'danger')
        return
    }

    setPasswordResetManagementAlert('Password reset was revoked for ' + userName)
    reloadPasswordResetTable()
}
