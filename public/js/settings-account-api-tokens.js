const apiTokenModal = new bootstrap.Modal('#apiTokenModal', {keyboard: false})

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('apiTokensPerPage').addEventListener('change', updateApiTokensPerPage)
    document.getElementById('createApiTokenButton').addEventListener('click', createApiToken)
    document.getElementById('revokeApiTokenButton').addEventListener('click', revokeApiToken)
    document.getElementById('copyCreatedApiTokenButton').addEventListener('click', copyCreatedApiToken)
    document.getElementById('finishApiTokenCreationButton').addEventListener('click', function () {
        redirectWithApiTokenNotification('created')
    })

    const urlParameters = new URLSearchParams(window.location.search)
    if (urlParameters.has('created')) {
        addAlert('apiTokenManagementAlerts', 'API token was created.', 'success')
        removeApiTokenQueryParameter('created')
    }
    if (urlParameters.has('revoked')) {
        addAlert('apiTokenManagementAlerts', 'API token was revoked.', 'success')
        removeApiTokenQueryParameter('revoked')
    }
    if (urlParameters.has('revokedAll')) {
        addAlert('apiTokenManagementAlerts', 'All API tokens were revoked.', 'success')
        removeApiTokenQueryParameter('revokedAll')
    }

    document.querySelectorAll('.api-token-row').forEach(function (row) {
        row.addEventListener('click', function () {
            showApiTokenDetailsModal(row)
        })
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault()
                showApiTokenDetailsModal(row)
            }
        })
    })
})

function updateApiTokensPerPage() {
    const url = new URL(window.location.href)
    url.searchParams.set('perPage', document.getElementById('apiTokensPerPage').value)
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function refreshApiTokensPage() {
    window.location.reload()
}

function removeApiTokenQueryParameter(name) {
    const url = new URL(window.location.href)
    url.searchParams.delete(name)

    window.history.replaceState(null, '', url.toString())
}

function redirectWithApiTokenNotification(name) {
    const url = new URL(window.location.href)
    url.searchParams.set(name, '1')

    window.location.href = url.toString()
}

function showCreateApiTokenModal() {
    document.getElementById('apiTokenModalTitle').textContent = 'Create personal API token'
    document.getElementById('apiTokenNameInput').value = ''
    document.getElementById('apiTokenNameInput').classList.remove('invalid-input')
    document.getElementById('apiTokenExpirationInput').value = '90'
    document.getElementById('apiTokenCreationForm').classList.remove('d-none')
    document.getElementById('apiTokenDetails').classList.add('d-none')
    document.getElementById('createdApiTokenContainer').classList.add('d-none')
    document.getElementById('copyCreatedApiTokenButton').textContent = 'Copy'
    document.getElementById('createApiTokenButton').classList.remove('d-none')
    document.getElementById('revokeApiTokenButton').classList.add('d-none')
    document.getElementById('finishApiTokenCreationButton').classList.add('d-none')
    document.getElementById('apiTokenModalCloseButton').classList.remove('d-none')
    document.getElementById('apiTokenModalAlerts').innerHTML = ''

    apiTokenModal.show()
}

function showApiTokenDetailsModal(row) {
    document.getElementById('apiTokenModalTitle').textContent = 'Personal API token'
    document.getElementById('apiTokenDetailsName').textContent = row.dataset.tokenName
    document.getElementById('apiTokenDetailsPrefix').textContent = row.dataset.tokenPrefix
    document.getElementById('apiTokenDetailsCreatedAt').textContent = row.dataset.tokenCreatedAt
    document.getElementById('apiTokenDetailsLastUsedAt').textContent = row.dataset.tokenLastUsedAt
    document.getElementById('apiTokenDetailsExpiresAt').textContent = row.dataset.tokenExpiresAt
    document.getElementById('apiTokenCreationForm').classList.add('d-none')
    document.getElementById('createdApiTokenContainer').classList.add('d-none')
    document.getElementById('apiTokenDetails').classList.remove('d-none')
    document.getElementById('createApiTokenButton').classList.add('d-none')
    document.getElementById('finishApiTokenCreationButton').classList.add('d-none')
    document.getElementById('apiTokenModalCloseButton').classList.remove('d-none')
    document.getElementById('apiTokenModalAlerts').innerHTML = ''

    const revokeButton = document.getElementById('revokeApiTokenButton')
    revokeButton.dataset.tokenId = row.dataset.tokenId
    revokeButton.dataset.tokenName = row.dataset.tokenName
    revokeButton.classList.remove('d-none')

    apiTokenModal.show()
}

async function createApiToken() {
    const nameInput = document.getElementById('apiTokenNameInput')
    const name = nameInput.value.trim()
    nameInput.classList.remove('invalid-input')
    document.getElementById('apiTokenModalAlerts').innerHTML = ''

    if (name.length === 0 || name.length > 100) {
        nameInput.classList.add('invalid-input')
        setApiTokenModalError('The token name must contain between 1 and 100 characters.')

        return
    }

    const createButton = document.getElementById('createApiTokenButton')
    const expirationValue = document.getElementById('apiTokenExpirationInput').value
    createButton.disabled = true

    let response
    try {
        response = await fetchWithCsrf(APPLICATION_URL + '/settings/account/api-tokens', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: name,
                expirationDays: expirationValue === '' ? null : Number(expirationValue),
            }),
        })
    } catch (error) {
        setApiTokenModalError()

        return
    } finally {
        createButton.disabled = false
    }

    if (response.ok === false) {
        setApiTokenModalError(await response.text())

        return
    }

    const responseData = await response.json()
    document.getElementById('createdApiTokenInput').value = responseData.token
    document.getElementById('createdApiTokenExpiration').textContent = responseData.expiresAt === null
        ? 'This token does not expire.'
        : 'This token expires at ' + responseData.expiresAt + ' UTC.'
    document.getElementById('apiTokenCreationForm').classList.add('d-none')
    document.getElementById('createdApiTokenContainer').classList.remove('d-none')
    createButton.classList.add('d-none')
    document.getElementById('finishApiTokenCreationButton').classList.remove('d-none')
    document.getElementById('apiTokenModalCloseButton').classList.add('d-none')
}

async function copyCreatedApiToken() {
    const button = document.getElementById('copyCreatedApiTokenButton')

    try {
        await navigator.clipboard.writeText(document.getElementById('createdApiTokenInput').value)
        button.textContent = 'Copied'
    } catch (error) {
        setApiTokenModalError('The token could not be copied. Select it and copy it manually.')
    }
}

async function revokeApiToken() {
    const revokeButton = document.getElementById('revokeApiTokenButton')
    const tokenId = revokeButton.dataset.tokenId
    const tokenName = revokeButton.dataset.tokenName
    const confirmed = await showConfirmationModal({
        title: 'Revoke API token',
        message: 'Are you sure you want to revoke the token "' + tokenName + '"?',
        confirmLabel: 'Revoke token',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    revokeButton.disabled = true
    await sendApiTokenDeleteRequest(APPLICATION_URL + '/settings/account/api-tokens/' + tokenId, 'revoked', true)
    revokeButton.disabled = false
}

async function revokeAllApiTokens() {
    const confirmed = await showConfirmationModal({
        title: 'Revoke all API tokens',
        message: 'Are you sure you want to revoke all personal API tokens?',
        confirmLabel: 'Revoke all tokens',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    await sendApiTokenDeleteRequest(APPLICATION_URL + '/settings/account/api-tokens', 'revokedAll')
}

async function sendApiTokenDeleteRequest(url, notificationName, showErrorInModal = false) {
    let response
    try {
        response = await fetchWithCsrf(url, {method: 'DELETE'})
    } catch (error) {
        showApiTokenDeleteError(showErrorInModal)

        return
    }

    if (response.ok === false) {
        showApiTokenDeleteError(showErrorInModal)

        return
    }

    redirectWithApiTokenNotification(notificationName)
}

function showApiTokenDeleteError(showInModal) {
    if (showInModal === true) {
        setApiTokenModalError('Could not revoke the API token.')
        apiTokenModal.show()

        return
    }

    addAlert('apiTokenManagementAlerts', 'Could not revoke the API token.', 'danger')
}

function setApiTokenModalError(message = 'Server error, please try again.') {
    const alerts = document.getElementById('apiTokenModalAlerts')
    alerts.innerHTML = ''

    const alert = document.createElement('div')
    alert.className = 'alert alert-danger'
    alert.setAttribute('role', 'alert')
    alert.textContent = message
    alerts.appendChild(alert)
}
