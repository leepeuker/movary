const emailSupportEnabledInput = document.getElementById('emailSupportEnabledInput');
const smtpSettingsFieldset = document.getElementById('smtpSettingsFieldset');
const emailSettingsTestButton = document.getElementById('emailSettingsTestButton');
const smtpHostInput = document.getElementById('smtpHostInput');
const smtpPortInput = document.getElementById('smtpPortInput');
const smtpEncryptionInput = document.getElementById('smtpEncryptionInput');
const smtpFromAddressInput = document.getElementById('smtpFromAddressInput');
const smtpWithAuthenticationInput = document.getElementById('smtpWithAuthenticationInput');
const smtpUserInput = document.getElementById('smtpUserInput');
const smtpPasswordInput = document.getElementById('smtpPasswordInput');

emailSupportEnabledInput.addEventListener('change', updateEmailSupportInputs);
smtpWithAuthenticationInput.addEventListener('change', updateSmtpAuthenticationInputs);

function updateEmailSupportInputs() {
    smtpSettingsFieldset.disabled = emailSupportEnabledInput.checked === false;
    emailSettingsTestButton.disabled = emailSupportEnabledInput.checked === false;
}

function updateSmtpAuthenticationInputs() {
    if (smtpWithAuthenticationInput.checked === false) {
        smtpUserInput.value = '';
        smtpPasswordInput.value = '';
        smtpPasswordInput.placeholder = '';
        smtpUserInput.disabled = true;
        smtpPasswordInput.disabled = true;

        return;
    }

    smtpUserInput.value = smtpUserInput.dataset.environmentValue;
    smtpPasswordInput.value = '';
    smtpPasswordInput.placeholder = smtpPasswordInput.dataset.configured === 'true' ? '***' : '';
    smtpUserInput.disabled = smtpUserInput.dataset.setInEnv === 'true';
    smtpPasswordInput.disabled = smtpPasswordInput.dataset.setInEnv === 'true';
}

function updateSmtpCredentialStateAfterSave() {
    if (smtpWithAuthenticationInput.checked === false) {
        if (
            smtpWithAuthenticationInput.disabled === false
            && smtpPasswordInput.dataset.setInEnv === 'false'
        ) {
            smtpPasswordInput.dataset.configured = 'false';
        }

        return;
    }

    if (smtpPasswordInput.disabled === false && smtpPasswordInput.value !== '') {
        smtpPasswordInput.dataset.configured = 'true';
        smtpPasswordInput.value = '';
        smtpPasswordInput.placeholder = '***';
    }
}

document.getElementById('emailSettingsUpdateButton').addEventListener('click', async () => {
    const response = await updateEmail();

    switch (response.status) {
        case 200:
            updateSmtpCredentialStateAfterSave();
            addAlert('alertEmailDiv', 'Update was successful', 'success');

            return;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertEmailDiv', errorMessage, 'danger');

            return;
        default:
            addAlert('alertEmailDiv', 'Unexpected server error', 'danger');
    }
});

function updateEmail() {
    return fetch(APPLICATION_URL + '/settings/server/email', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify(getEditableSmtpSettings())
    });
}

function getEditableSmtpSettings() {
    const smtpSettings = {};

    if (emailSupportEnabledInput.disabled === false) {
        smtpSettings.emailEnabled = emailSupportEnabledInput.checked;
    }
    if (emailSupportEnabledInput.checked === false) {
        return smtpSettings;
    }

    if (smtpHostInput.disabled === false) {
        smtpSettings.smtpHost = smtpHostInput.value;
    }
    if (smtpPortInput.disabled === false) {
        smtpSettings.smtpPort = smtpPortInput.value;
    }
    if (smtpFromAddressInput.disabled === false) {
        smtpSettings.smtpFromAddress = smtpFromAddressInput.value;
    }
    if (smtpEncryptionInput.disabled === false) {
        smtpSettings.smtpEncryption = smtpEncryptionInput.value;
    }
    if (smtpWithAuthenticationInput.disabled === false) {
        smtpSettings.smtpWithAuthentication = smtpWithAuthenticationInput.checked;
    }
    if (smtpUserInput.disabled === false) {
        smtpSettings.smtpUser = smtpUserInput.value;
    }
    if (smtpPasswordInput.disabled === false && smtpPasswordInput.value !== '') {
        smtpSettings.smtpPassword = smtpPasswordInput.value;
    }

    return smtpSettings;
}

const testEmailModal = new bootstrap.Modal('#testEmailModal')

emailSettingsTestButton.addEventListener('click', async () => {
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

    const response = await testEmail(recipient);

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

function testEmail(recipient) {
    return fetch(APPLICATION_URL + '/settings/server/email-test', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'recipient': recipient,
            ...getEditableSmtpSettings()
        })
    });
}
