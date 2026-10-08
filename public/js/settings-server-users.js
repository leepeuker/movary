const userModal = new bootstrap.Modal('#userModal', {keyboard: false})

const table = document.getElementById('usersTable');
const rows = table.getElementsByTagName('tr');
const passwordResetUserButton = document.getElementById('passwordResetUserButton');

registerTableRowClickEvent()
document.getElementById('usersPerPage').addEventListener('change', updateUsersPerPage)

const urlParams = new URLSearchParams(window.location.search)
const userCreatedName = urlParams.get('userCreated')
if (userCreatedName) {
    setUserManagementAlert('User was created: ' + userCreatedName)
    removeQueryParameter('userCreated')
}
const userUpdatedName = urlParams.get('userUpdated')
if (userUpdatedName) {
    setUserManagementAlert('User was updated: ' + userUpdatedName)
    removeQueryParameter('userUpdated')
}
const userDeletedName = urlParams.get('userDeleted')
if (userDeletedName) {
    setUserManagementAlert('User was deleted: ' + userDeletedName)
    removeQueryParameter('userDeleted')
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
                this.cells[0].textContent,
                this.cells[1].textContent,
                this.cells[2].textContent,
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

    redirectWithNotification('userCreated', document.getElementById('userModalNameInput').value)
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

    redirectWithNotification('userUpdated', document.getElementById('userModalNameInput').value)
})

document.getElementById('deleteUserButton').addEventListener('click', async () => {
    const confirmed = await showConfirmationModal({
        title: 'Delete user',
        message: 'Are you sure you want to delete the user?',
        confirmLabel: 'Delete user',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    const response = await fetch(APPLICATION_URL + '/settings/users/' + document.getElementById('userModalIdInput').value, {
        method: 'DELETE'
    });

    if (response.status !== 200) {
        setUserModalAlertServerError()
        return
    }

    redirectWithNotification('userDeleted', document.getElementById('userModalNameInput').value)
})

function setUserManagementAlert(message, type = 'success') {
    const userManagementAlerts = document.getElementById('userManagementAlerts');
    userManagementAlerts.classList.remove('d-none')
    userManagementAlerts.innerHTML = ''
    userManagementAlerts.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
    userManagementAlerts.style.textAlign = 'center'
}

function updateUsersPerPage() {
    const url = new URL(window.location.href)
    url.searchParams.set('perPage', document.getElementById('usersPerPage').value)
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function refreshUsersPage() {
    window.location.reload()
}

function applyUserFilters() {
    const url = new URL(window.location.href)
    const role = document.getElementById('userFilterRole').value

    if (role === '') {
        url.searchParams.delete('role')
    } else {
        url.searchParams.set('role', role)
    }
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function resetUserFilters() {
    document.getElementById('userFilterRole').value = ''
    applyUserFilters()
}

function removeQueryParameter(name) {
    const url = new URL(window.location.href)
    url.searchParams.delete(name)

    window.history.replaceState(null, '', url.toString())
}

function redirectWithNotification(name, value) {
    const url = new URL(window.location.href)
    url.searchParams.set(name, value)

    window.location.href = url.toString()
}

async function sendPasswordReset(userId, userName) {
    const confirmed = await showConfirmationModal({
        title: 'Send password reset email',
        message: 'Send a new password reset email to ' + userName + '?',
        confirmLabel: 'Send email',
    })

    if (confirmed === false) {
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
    userModal.hide()
}
