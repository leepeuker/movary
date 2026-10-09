const revokeAllPasswordResetsButton = document.getElementById('revokeAllPasswordResetsButton');

document.getElementById('passwordResetsPerPage').addEventListener('change', updatePasswordResetsPerPage)

document.querySelectorAll('.password-reset-revoke-button').forEach((button) => {
    button.addEventListener('click', () => revokePasswordReset(button))
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

function updatePasswordResetsPerPage() {
    const url = new URL(window.location.href)
    url.searchParams.set('perPage', document.getElementById('passwordResetsPerPage').value)
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function refreshPasswordResetsPage() {
    window.location.reload()
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

async function revokePasswordReset(button) {
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
        setPasswordResetManagementAlert('Could not revoke password reset.', 'danger')

        return
    } finally {
        button.disabled = false
    }

    if (response.ok === false) {
        setPasswordResetManagementAlert('Could not revoke password reset.', 'danger')
        return
    }

    redirectWithNotification('resetRevoked', userName)
}
