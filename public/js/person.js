function toggleBiography() {
    let expandContainer = document.getElementById('expandContainer');
    if (document.getElementsByClassName('truncated').length > 0) {
        document.getElementById('biographyParagraph').classList.remove('truncated');
        expandContainer.getElementsByTagName('i')[0].classList.remove('bi-chevron-down');
        expandContainer.getElementsByTagName('i')[0].classList.add('bi-chevron-up');
        expandContainer.children[1].innerHTML = 'Show less&#8230;';
    } else {
        document.getElementById('biographyParagraph').classList.add('truncated');
        expandContainer.getElementsByTagName('i')[0].classList.add('bi-chevron-down');
        expandContainer.getElementsByTagName('i')[0].classList.remove('bi-chevron-up');
        expandContainer.children[1].innerHTML = 'Show more&#8230;';
    }
}

document.addEventListener("DOMContentLoaded", () => {
    let biographyHeight = document.getElementById('biographyParagraph').offsetHeight;
    let windowHeight = window.outerHeight;
    if (((biographyHeight / windowHeight) * 100) > 20) {
        document.getElementById('biographyParagraph').classList.add('truncated');
        document.getElementById('expandContainer').classList.remove('d-none');
    }
});

function refreshTmdbData() {
    disableMoreModalButtons(true)
    removeAlert('alertPersonOptionModalDiv')

    sendRequest('refresh-tmdb').then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertPersonOptionModalDiv', 'Cannot refresh TMDB data', 'danger')
    }).finally(() => {
        disableMoreModalButtons(false)
    })
}

function hideInTopLists() {
    disableMoreModalButtons(true)
    removeAlert('alertPersonOptionModalDiv')

    sendRequest('hide-in-top-lists').then(() => {
        document.getElementById('hideInTopListsButton').classList.add('d-none');
        document.getElementById('showInTopListsButton').classList.remove('d-none');
        addAlert('alertPersonOptionModalDiv', 'Person not visible in top lists anymore', 'success')
    }).catch(() => {
        addAlert('alertPersonOptionModalDiv', 'Cannot hide person in top lists', 'danger')
    }).finally(() => {
        disableMoreModalButtons(false)
    })
}

function showInTopLists() {
    disableMoreModalButtons(true)
    removeAlert('alertPersonOptionModalDiv')

    sendRequest('show-in-top-lists').then(() => {
        document.getElementById('hideInTopListsButton').classList.remove('d-none');
        document.getElementById('showInTopListsButton').classList.add('d-none');
        addAlert('alertPersonOptionModalDiv', 'Person visible in top lists again', 'success')
    }).catch(() => {
        addAlert('alertPersonOptionModalDiv', 'Cannot show person in top lists', 'danger')
    }).finally(() => {
        disableMoreModalButtons(false)
    })
}

async function sendRequest(action) {
    const personId = document.getElementById('personId').value;
    const response = await fetch(APPLICATION_URL + '/persons/' + personId + '/' + action)

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

function disableMoreModalButtons(disable) {
    document.getElementById('refreshTmdbDataButton').disabled = disable;
    document.getElementById('hideInTopListsButton').disabled = disable;
    document.getElementById('showInTopListsButton').disabled = disable;
}
