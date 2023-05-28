const smtpHostInput = document.getElementById('smtpHostInput');
const smtpPortInput = document.getElementById('smtpPortInput');
const smtpFromAddressInput = document.getElementById('smtpFromAddressInput');
const smtpWithAuthenticationInput = document.getElementById('smtpWithAuthenticationInput');
const smtpUserInput = document.getElementById('smtpUserInput');
const smtpPasswordInput = document.getElementById('smtpPasswordInput');

document.getElementById('emailSettingsUpdateButton').addEventListener('click', async () => {
    smtpHostInput.classList.remove('invalid-input');
    smtpPortInput.classList.remove('invalid-input');
    smtpFromAddressInput.classList.remove('invalid-input');

    let smtpHostValue = null;
    let smtpPortValue = null;
    let smtpFromAddressInputValue = null;

    let error = false

    if (smtpHostInput.disabled === false) {
        smtpHostValue = smtpHostInput.value;
        if (smtpHostValue === '') {
            smtpHostInput.classList.add('invalid-input');
            error = true
        }
    }
    if (smtpPortInput.disabled === false) {
        smtpPortValue = smtpPortInput.value;
        if (smtpPortValue === '') {
            smtpPortInput.classList.add('invalid-input');
            error = true
        }
    }
    if (smtpFromAddressInput.disabled === false) {
        smtpFromAddressInputValue = smtpFromAddressInput.value;
        if (smtpFromAddressInputValue === '') {
            smtpFromAddressInput.classList.add('invalid-input');
            error = true
        }
    }

    if (error === true) {
        addAlert('alertEmailDiv', 'Please enter missing SMPT information', 'danger');

        return
    }

    const response = await updateEmail(
        smtpHostValue,
        smtpPortValue,
        smtpFromAddressInputValue,
        smtpWithAuthenticationInput.value,
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

function updateEmail(smtpHost, smtpPort, smtpFromAddress, smtpWithAuthentication, smtpUser, smtpPassword) {
    return fetch('/settings/server/email', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'smtpHost': smtpHost,
            'smtpPort': smtpPort,
            'smtpFromAddress': smtpFromAddress,
            'smtpWithAuthentication': smtpWithAuthentication,
            'smtpUser': smtpUser,
            'smtpPassword': smtpPassword
        })
    });
}
