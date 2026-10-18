const revokeAllPasswordResetsButton = document.getElementById('revokeAllPasswordResetsButton');

document.getElementById('passwordResetsPerPage').addEventListener('change', updatePasswordResetsPerPage)
document.getElementById('passwordResetForm').addEventListener('submit', createPasswordReset)

document.querySelectorAll('.password-reset-row').forEach((row) => {
    row.addEventListener('click', () => showPasswordResetDetailsModal(row))
    row.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return
        }

        event.preventDefault()
        showPasswordResetDetailsModal(row)
    })
})

const urlParams = new URLSearchParams(window.location.search)
const revokedUserName = urlParams.get('resetRevoked')
if (revokedUserName) {
    setPasswordResetManagementAlert('Password reset was revoked for ' + revokedUserName)
    removeQueryParameter('resetRevoked')
}
if (urlParams.get('allResetsRevoked')) {
    setPasswordResetManagementAlert('All password reset links were revoked.')
    removeQueryParameter('allResetsRevoked')
}
const createdUserName = urlParams.get('resetCreated')
if (createdUserName) {
    setPasswordResetManagementAlert('Password reset email was scheduled for ' + createdUserName)
    removeQueryParameter('resetCreated')
}
const resentUserName = urlParams.get('resetResent')
if (resentUserName) {
    setPasswordResetManagementAlert('Password reset email was resent to ' + resentUserName)
    removeQueryParameter('resetResent')
}

function updatePasswordResetsPerPage() {
    const url = new URL(window.location.href)
    url.searchParams.set('perPage', document.getElementById('passwordResetsPerPage').value)
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function refreshPasswordResetsPage() {
    window.location.reload()
}

function showCreatePasswordResetModal() {
    document.getElementById('passwordResetForm').reset()
    document.getElementById('passwordResetModalTitle').textContent = 'Create password reset'
    document.getElementById('passwordResetCreationForm').classList.remove('d-none')
    document.getElementById('passwordResetDetails').classList.add('d-none')
    document.getElementById('createPasswordResetButton').classList.remove('d-none')
    document.getElementById('revokePasswordResetButton').classList.add('d-none')
    document.getElementById('resendPasswordResetButton').classList.add('d-none')
    clearPasswordResetModalError()
    bootstrap.Modal.getOrCreateInstance('#passwordResetModal').show()
}

function showPasswordResetDetailsModal(row) {
    document.getElementById('passwordResetModalTitle').textContent = 'Password reset'
    document.getElementById('passwordResetDetailsUser').textContent = row.dataset.userName
    document.getElementById('passwordResetDetailsEmail').textContent = row.dataset.userEmail
    document.getElementById('passwordResetDetailsCreatedAt').textContent = row.dataset.createdAt
    document.getElementById('passwordResetDetailsExpiresAt').textContent = row.dataset.expiresAt
    document.getElementById('passwordResetCreationForm').classList.add('d-none')
    document.getElementById('passwordResetDetails').classList.remove('d-none')
    document.getElementById('createPasswordResetButton').classList.add('d-none')
    clearPasswordResetModalError()

    const revokeButton = document.getElementById('revokePasswordResetButton')
    const resendButton = document.getElementById('resendPasswordResetButton')
    revokeButton.dataset.userId = row.dataset.userId
    revokeButton.dataset.userName = row.dataset.userName
    resendButton.dataset.userId = row.dataset.userId
    resendButton.dataset.userName = row.dataset.userName
    revokeButton.classList.remove('d-none')
    resendButton.classList.remove('d-none')

    bootstrap.Modal.getOrCreateInstance('#passwordResetModal').show()
}

function clearPasswordResetModalError() {
    const errorAlert = document.getElementById('passwordResetModalError')
    errorAlert.classList.add('d-none')
    errorAlert.textContent = ''
}

function showPasswordResetModalError(message) {
    const errorAlert = document.getElementById('passwordResetModalError')
    errorAlert.textContent = message
    errorAlert.classList.remove('d-none')
    bootstrap.Modal.getOrCreateInstance('#passwordResetModal').show()
}

async function createPasswordReset(event) {
    event.preventDefault()

    const userSelect = document.getElementById('passwordResetUser')
    if (event.currentTarget.reportValidity() === false) {
        return
    }

    const submitButton = document.getElementById('createPasswordResetButton')
    const errorAlert = document.getElementById('passwordResetModalError')
    submitButton.disabled = true
    errorAlert.classList.add('d-none')
    errorAlert.textContent = ''

    try {
        const response = await fetchWithCsrf(
            APPLICATION_URL + '/settings/users/' + userSelect.value + '/password-reset',
            {method: 'POST'},
        )

        if (response.ok === false) {
            errorAlert.textContent = await response.text() || 'Could not schedule password reset email.'
            errorAlert.classList.remove('d-none')
            return
        }

        const userName = userSelect.options[userSelect.selectedIndex].dataset.userName
        redirectWithNotification('resetCreated', userName)
    } catch (error) {
        errorAlert.textContent = 'Could not schedule password reset email.'
        errorAlert.classList.remove('d-none')
    } finally {
        submitButton.disabled = false
    }
}

function removeQueryParameter(name) {
    const url = new URL(window.location.href)
    url.searchParams.delete(name)

    window.history.replaceState(null, '', url.toString())
}

function redirectWithNotification(name, value = '1') {
    const url = new URL(window.location.href)
    url.searchParams.set(name, value)

    window.location.href = url.toString()
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

async function revokeAllPasswordResets() {
    const confirmed = await showConfirmationModal({
        title: 'Revoke all reset links',
        message: 'Are you sure you want to revoke all active password reset links? Existing reset links will no longer be valid.',
        confirmLabel: 'Revoke all',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    revokeAllPasswordResetsButton.disabled = true

    const response = await fetchWithCsrf(APPLICATION_URL + '/settings/password-resets', {
        method: 'DELETE'
    })

    if (response.ok === false) {
        setPasswordResetManagementAlert('Could not revoke all password reset links.', 'danger')
        revokeAllPasswordResetsButton.disabled = false
        return
    }

    redirectWithNotification('allResetsRevoked')
}

async function revokePasswordReset() {
    const button = document.getElementById('revokePasswordResetButton')
    const userId = button.dataset.userId
    const userName = button.dataset.userName

    const confirmed = await showConfirmationModal({
        title: 'Revoke password reset',
        message: 'Are you sure you want to revoke the password reset for "' + userName + '"?',
        confirmLabel: 'Revoke reset link',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    button.disabled = true

    let response
    try {
        response = await fetchWithCsrf(APPLICATION_URL + '/settings/users/' + userId + '/password-reset', {
            method: 'DELETE'
        })
    } catch (error) {
        showPasswordResetModalError('Could not revoke password reset.')

        return
    } finally {
        button.disabled = false
    }

    if (response.ok === false) {
        showPasswordResetModalError('Could not revoke password reset.')
        return
    }

    redirectWithNotification('resetRevoked', userName)
}

async function resendPasswordReset() {
    const button = document.getElementById('resendPasswordResetButton')
    const userId = button.dataset.userId
    const userName = button.dataset.userName
    const confirmed = await showConfirmationModal({
        title: 'Resend password reset email',
        message: 'Send a new password reset email to "' + userName + '"? The existing reset link will be replaced.',
        confirmLabel: 'Resend email',
    })

    if (confirmed === false) {
        return
    }

    button.disabled = true

    let response
    try {
        response = await fetchWithCsrf(APPLICATION_URL + '/settings/users/' + userId + '/password-reset', {
            method: 'POST'
        })
    } catch (error) {
        showPasswordResetModalError('Could not resend password reset email.')

        return
    } finally {
        button.disabled = false
    }

    if (response.ok === false) {
        showPasswordResetModalError(await response.text() || 'Could not resend password reset email.')
        return
    }

    redirectWithNotification('resetResent', userName)
}
