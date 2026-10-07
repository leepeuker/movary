const passwordResetsTable = document.getElementById('passwordResetsTable');
const revokeAllPasswordResetsButton = document.getElementById('revokeAllPasswordResetsButton');

reloadPasswordResetTable()

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
