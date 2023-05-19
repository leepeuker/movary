generatedReleases()

async function fetchLatestReleases() {
    const response = await fetch('https://api.github.com/repos/leepeuker/movary/releases?per_page=10')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
}

function generatedReleases() {
    fetchLatestReleases().then(latestReleases => {
        latestReleases.forEach((latestRelease) => {
            document.getElementById('loadingSpinner').classList.add('d-none')
            addReleaseToList(latestRelease)
        })
    }).catch((error) => {
        console.log(error)
        addAlert('alertWebhookUrlDiv', 'Could not generate webhook url', 'danger')
    })
}


function addReleaseToList(latestRelease) {
    let releasesList = document.getElementById('latestReleases')
    let releaseListItem = document.createElement('li');
    let releaseListItemTitle = document.createElement('span');

    releaseListItemTitle.innerHTML = latestRelease.name

    releaseListItem.appendChild(releaseListItemTitle);
    releaseListItem.classList.add('list-group-item', 'list-group-item-action');
    releaseListItem.dataset.body = latestRelease.body
    releaseListItem.dataset.name = latestRelease.name
    releaseListItem.style.cursor = 'pointer'
    releaseListItem.setAttribute("onclick","showReleaseModal(this)");

    releasesList.appendChild(releaseListItem);
}

function showReleaseModal (element) {
    const modal = new bootstrap.Modal('#appReleaseModal');

    document.getElementById('appReleaseModalTitle').innerHTML = element.dataset.name
    document.getElementById('appReleaseModalBody').innerHTML = element.dataset.body

    modal.show()
}
