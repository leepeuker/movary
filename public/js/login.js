const MOVARY_CLIENT_IDENTIFIER = 'Movary Web';

async function submitCredentials() {
    const urlParams = new URLSearchParams(window.location.search);
    const safeRedirect = getSafeRedirect(urlParams.get('redirect'), APPLICATION_URL);

    const response = await loginRequest();

    if (response.status === 200) {
        window.location.href = safeRedirect
        return;
    }

    const forbiddenPageAlert = document.getElementById('forbiddenPageAlert');
    if (forbiddenPageAlert) {
        forbiddenPageAlert.classList.add('d-none');
    }

    if (response.status === 400) {
        const error = await response.json();

        if (error['error'] === 'MissingTotpCode') {
            document.getElementById('loginForm').classList.add('d-none');
            document.getElementById('totpForm').classList.remove('d-none');
            return
        }

        addAlert('loginErrors', error['message'], 'danger', false);
        return;
    }

    if (response.status === 401) {
        const error = await response.json();

        if (error['error'] === 'InvalidTotpCode') {
            addAlert('totpErrors', error['message'], 'danger', false);
            return
        }

        if (error['error'] === 'InvalidCredentials') {
            addAlert('loginErrors', error['message'], 'danger', false);
            return
        }
    }

    if (response.status === 429) {
        const retryAfter = response.headers.get('Retry-After');
        const message = retryAfter
            ? 'Too many login attempts. Please try again in ' + retryAfter + ' seconds.'
            : 'Too many login attempts. Please try again later.';
        addAlert('loginErrors', message, 'danger', false);
        return;
    }

    addAlert('loginErrors', 'Unexpected server error', 'danger', false);
}

function loginRequest() {
    return fetch(APPLICATION_URL + '/api/authentication/token', {
        method: 'POST',
        headers: {
            'Content-type': 'application/json',
            'X-Movary-Client': MOVARY_CLIENT_IDENTIFIER
        },
        body: JSON.stringify({
            'email': document.getElementById('email').value,
            'password': document.getElementById('password').value,
            'rememberMe': document.getElementById('rememberMe').checked,
            'totpCode': document.getElementById('totpCode').value,
        })
    });
}

function submitCredentialsOnEnter(event) {
    if (event.keyCode === 13) {
        submitCredentials()
    }
}

function getSafeRedirect(redirectGetParameter, baseUrl) {
    const fallbackRedirect = baseUrl + '/';

    if (!redirectGetParameter) {
        return fallbackRedirect;
    }

    try {
        const applicationUrl = new URL(baseUrl || '/', window.location.origin);
        const parsedRedirect = new URL(redirectGetParameter, applicationUrl);
        const applicationPath = applicationUrl.pathname.replace(/\/$/, '');
        const redirectIsWithinApplicationPath = applicationPath === ''
            || parsedRedirect.pathname === applicationPath
            || parsedRedirect.pathname.startsWith(applicationPath + '/');

        if (parsedRedirect.origin !== applicationUrl.origin || !redirectIsWithinApplicationPath) {
            return fallbackRedirect;
        }

        return parsedRedirect.href;
    } catch {
        return fallbackRedirect;
    }
}
