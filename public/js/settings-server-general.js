const tmdbApiKeyInput = document.getElementById('tmdbApiKeyInput');
const applicationUrlInput = document.getElementById('applicationUrlInput');
const applicationNameInput = document.getElementById('applicationNameInput');
const applicationTimezoneSelect = document.getElementById('applicationTimezoneSelect');

document.getElementById('generalServerUpdateButton').addEventListener('click', async () => {
    tmdbApiKeyInput.classList.remove('invalid-input');
    applicationUrlInput.classList.remove('invalid-input');
    applicationNameInput.classList.remove('invalid-input');
    applicationTimezoneSelect.classList.remove('invalid-input');

    let tmdbApiKeyInputValue = null;

    if (tmdbApiKeyInput.disabled === false) {
        tmdbApiKeyInputValue = tmdbApiKeyInput.value;

        if (tmdbApiKeyInputValue === '') {
            addAlert('alertGeneralServerDiv', 'TMDB API Key is not set', 'danger');
            tmdbApiKeyInput.classList.add('invalid-input');

            return;
        }
    }

    if (applicationUrlInput.value !== '') {
        if (isValidUrl(applicationUrlInput.value) === false) {
            addAlert('alertGeneralServerDiv', 'Application url not a valid url. Valid example: http://localhost', 'danger');
            applicationUrlInput.classList.add('invalid-input');
            return;
        }
    }

    if (applicationNameInput.value !== '') {
        if (isValidName(applicationNameInput.value) === false) {
            addAlert('alertGeneralServerDiv', 'Application name not valid. Must only contain letters, numbers, spaces or \'-\' and have max 15 characters', 'danger');
            applicationNameInput.classList.add('invalid-input');
            return;
        }
    }

    const response = await updateGeneral(tmdbApiKeyInputValue, applicationUrlInput.value, applicationNameInput.value, applicationTimezoneSelect.value);

    switch (response.status) {
        case 200:
            addAlert('alertGeneralServerDiv', 'Update was successful', 'success');

            if (applicationNameInput.value == '') {
                document.getElementById('navbarBrand').innerText = 'Movary'
                return
            }

            document.getElementById('navbarBrand').innerText = applicationNameInput.value ?? 'Movary'

            return;
        case 400:
            const errorMessage = await response.text();

            tmdbApiKeyInput.classList.add('invalid-input');
            addAlert('alertGeneralServerDiv', errorMessage, 'danger');

            return;
        default:
            addAlert('alertGeneralServerDiv', 'Unexpected server error', 'danger');
    }
});

function updateGeneral(tmdbApiKey, applicationUrl, applicationName, applicationTimezone) {
    return fetch('/settings/server/general', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'tmdbApiKey': tmdbApiKey,
            'applicationUrl': applicationUrl,
            'applicationName': applicationName,
            'applicationTimezone': applicationTimezone,
        })
    });
}

function isValidUrl(urlString) {
    try {
        new URL(urlString);
        return true;
    } catch (err) {
        return false;
    }
}
function isValidName(nameString) {
    const alphanumericRegex = /^[a-zA-Z0-9\s]+$/;

    if (alphanumericRegex.test(nameString) === false) {
        return false;
    }

    return nameString.length <= 15;
}
