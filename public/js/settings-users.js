const userModal = new bootstrap.Modal('#userModal', {keyboard: false})

const table = document.getElementById('usersTable');
const rows = table.getElementsByTagName('tr');

for (let i = 0; i < rows.length; i++) {
    if (i === 0) continue

    rows[i].onclick = function () {
        prepareEditUserModal()

        document.getElementById('userModalIdInput').value = this.cells[0].innerHTML
        document.getElementById('userModalNameInput').value = this.cells[1].innerHTML
        document.getElementById('userModalEmailInput').value = this.cells[2].innerHTML
        document.getElementById('userModalIsAdminInput').checked = this.cells[3].innerHTML === '1'

        userModal.show()
    };
}

function showCreateUserModal() {
    prepareCreateUserModal()
    userModal.show()
}

function prepareCreateUserModal(name) {
    document.getElementById('userModalHeaderTitle').innerHTML = 'Create user'

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
}

function prepareEditUserModal() {
    document.getElementById('userModalHeaderTitle').innerHTML = 'Edit user'

    document.getElementById('userModalPasswordInput').required = false
    document.getElementById('userModalRepeatPasswordInput').required = false

    document.getElementById('userModalPasswordInputRequiredStar').classList.add('d-none')
    document.getElementById('userModalRepeatPasswordInputRequiredStar').classList.add('d-none')
    document.getElementById('userModalFooterCreateButton').classList.add('d-none')
    document.getElementById('userModalFooterButtons').classList.remove('d-none')
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
        if (passwordInput.value.length < 8 || passwordInput.value !== passwordRepeatInput.value) {
            if (passwordInput.value.length < 8) {
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
        document.getElementById('userModalAlerts').innerHTML = '<div class="alert alert-danger" role="alert">Server error.</div>'

        return
    }

    location.href = '/settings/users'
})

document.getElementById('updateUserButton').addEventListener('click', async () => {
    if (validateCreateUserInput() === true) {
        return;
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
        })
    })

    if (response.status !== 200) {
        document.getElementById('userModalAlerts').innerHTML = '<div class="alert alert-danger" role="alert">Server error.</div>'

        return
    }

    location.href = '/settings/users'
})

document.getElementById('deleteUserButton').addEventListener('click', async () => {
    if (confirm('Are you sure you want to delete the user?') === false) {
        return
    }

    const response = await fetch('/api/users/' + document.getElementById('userModalIdInput').value, {
        method: 'DELETE'
    });

    if (response.status !== 200) {
        document.getElementById('userModalAlerts').innerHTML = '<div class="alert alert-danger" role="alert">Server error.</div>'

        return
    }

    location.href = '/settings/users'
})
