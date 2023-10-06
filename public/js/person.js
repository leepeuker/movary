function toggleBiography()
{
    let expandContainer = document.getElementById('expandContainer');
    if(document.getElementsByClassName('truncated').length > 0) {
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
    if(((biographyHeight / windowHeight) * 100) > 20) {
        document.getElementById('biographyParagraph').classList.add('truncated');
        document.getElementById('expandContainer').classList.remove('d-none');
    }
});

function refreshTmdbData() {
    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('hideInTopListsButton').disabled = true;
    document.getElementById('showInTopListsButton').disabled = true;

    refreshTmdbDataRequest().then(() => {
        location.reload()
    }).catch(() => {
        document.getElementById('refreshTmdbDataButton').disabled = false;
        document.getElementById('hideInTopListsButton').disabled = false;
        document.getElementById('showInTopListsButton').disabled = false;
    })
}

function hideInTopLists() {
    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('hideInTopListsButton').disabled = true;
    document.getElementById('showInTopListsButton').disabled = true;

    hideInTopListsRequest().then(() => {
        document.getElementById('hideInTopListsButton').classList.add('d-none');
        document.getElementById('showInTopListsButton').classList.remove('d-none');
    }).catch(() => {
        document.getElementById('refreshTmdbDataButton').disabled = false;
        document.getElementById('hideInTopListsButton').disabled = false;
        document.getElementById('showInTopListsButton').disabled = false;
    })
}

function showInTopLists() {
    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('hideInTopListsButton').disabled = true;
    document.getElementById('showInTopListsButton').disabled = true;

    showInTopListsRequest().then(() => {
        document.getElementById('hideInTopListsButton').classList.remove('d-none');
        document.getElementById('showInTopListsButton').classList.add('d-none');
    }).catch(() => {
        document.getElementById('refreshTmdbDataButton').disabled = false;
        document.getElementById('hideInTopListsButton').disabled = false;
        document.getElementById('showInTopListsButton').disabled = false;
    })
}

async function hideInTopListsRequest() {
    const response = await fetch('/persons/' + getPersonId() + '/hide-in-top-lists')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

async function showInTopListsRequest() {
    const response = await fetch('/persons/' + getPersonId() + '/show-in-top-lists')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

function getPersonId() {
    return document.getElementById('personId').value
}
