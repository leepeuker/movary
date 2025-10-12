const smtpHostInput = document.getElementById('smtpHostInput');
const smtpPortInput = document.getElementById('smtpPortInput');
const smtpEncryptionInput = document.getElementById('smtpEncryptionInput');
const smtpFromAddressInput = document.getElementById('smtpFromAddressInput');
const smtpWithAuthenticationInput = document.getElementById('smtpWithAuthenticationInput');
const smtpUserInput = document.getElementById('smtpUserInput');
const smtpPasswordInput = document.getElementById('smtpPasswordInput');

document.getElementById('emailSettingsUpdateButton').addEventListener('click', async () => {
    const response = await updateEmail(
        smtpHostInput.value,
        smtpPortInput.value,
        smtpFromAddressInput.value,
        smtpEncryptionInput.value,
        smtpWithAuthenticationInput.checked,
        smtpUserInput.value,
        smtpPasswordInput.value
    );

    switch (response.status) {
        case 200:
            addAlert('alertEmailDiv', 'Update was successful', 'success');

            return;
        case 400:
            const errorMessage = await response.text();

            tmdbApiKeyInput.classList.add('invalid-input');
            addAlert('alertEmailDiv', errorMessage, 'danger');

            return;
        default:
            addAlert('alertEmailDiv', 'Unexpected server error', 'danger');
    }
});

function updateEmail(smtpHost, smtpPort, smtpFromAddress, smtpEncryption, smtpWithAuthentication, smtpUser, smtpPassword) {
    return fetch(APPLICATION_URL + '/settings/server/email', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'smtpHost': smtpHost,
            'smtpPort': smtpPort,
            'smtpFromAddress': smtpFromAddress,
            'smtpEncryption': smtpEncryption,
            'smtpWithAuthentication': smtpWithAuthentication,
            'smtpUser': smtpUser,
            'smtpPassword': smtpPassword
        })
    });
}

const testEmailModal = new bootstrap.Modal('#testEmailModal')

document.getElementById('emailSettingsTestButton').addEventListener('click', async () => {
    testEmailModal.show()
});

document.getElementById('testEmailModal').addEventListener('show.bs.modal', function () {
    removeAlert('testEmailModalAlerts')
})

document.getElementById('sendTestEmailButton').addEventListener('click', async () => {
    const recipient = document.getElementById('testEmailAddressRecipientInput').value;
    const loadingSpinner = document.getElementById('testEmailLoadingSpinner');

    if (recipient === '') {
        addAlert('testEmailModalAlerts', 'Recipient email address must be set.', 'danger');

        return;
    }

    removeAlert('testEmailModalAlerts')
    loadingSpinner.classList.remove('d-none')

    const response = await testEmail(
        recipient,
        smtpHostInput.value,
        smtpPortInput.value,
        smtpFromAddressInput.value,
        smtpEncryptionInput.value,
        smtpWithAuthenticationInput.value,
        smtpUserInput.value,
        smtpPasswordInput.value
    );

    loadingSpinner.classList.add('d-none')

    switch (response.status) {
        case 200:
            addAlert('testEmailModalAlerts', 'Test email was successfully sent', 'success');

            return;
        case 400:
            const errorMessage = await response.text();

            addAlert('testEmailModalAlerts', 'Error: ' + errorMessage, 'danger');

            return;
        default:
            addAlert('testEmailModalAlerts', 'Unexpected server error.', 'danger');
    }
});

function testEmail(recipient, smtpHost, smtpPort, smtpFromAddress, smtpEncryption, smtpWithAuthentication, smtpUser, smtpPassword) {
    return fetch(APPLICATION_URL + '/settings/server/email-test', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'recipient': recipient,
            'smtpHost': smtpHost,
            'smtpPort': smtpPort,
            'smtpEncryption': smtpEncryption,
            'smtpFromAddress': smtpFromAddress,
            'smtpWithAuthentication': smtpWithAuthentication,
            'smtpUser': smtpUser,
            'smtpPassword': smtpPassword
        })
    });
}
