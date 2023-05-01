const tmdbApiKeyInput = document.getElementById('tmdbApiKeyInput');
const serverDomainInput = document.getElementById('serverDomainInput');
const serverDomainRegex = /^\w*(\.\w{2,})+/;

document.getElementById('generalServerUpdateButton').addEventListener('click', async () => {
    tmdbApiKeyInput.classList.remove('invalid-input');

    let tmdbApiKeyInputValue = null;

    if (tmdbApiKeyInput.disabled === false) {
        tmdbApiKeyInputValue = tmdbApiKeyInput.value;

        if (tmdbApiKeyInputValue == '') {
            addAlert('alertGeneralServerDiv', 'TMDB API Key is not set', 'danger');
            tmdbApiKeyInput.classList.add('invalid-input');

            return;
        }
    }

    if(!serverDomainRegex.test(serverDomainInput.value)) {
        addAlert('alertGeneralServerDiv', 'Domain name is invalid', 'danger');
        serverDomainInput.classList.add('invalid-input');
        return;
    }

    const response = await updateGeneral(tmdbApiKeyInputValue, serverDomainInput.value);

    switch (response.status) {
        case 200:
            addAlert('alertGeneralServerDiv', 'Update was successful', 'success');

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

function updateGeneral(tmdbApiKey, serverDomain) {
    return fetch('/settings/server/general', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'tmdbApiKey': tmdbApiKey,
            'serverDomain': serverDomain
        })
    });
};
