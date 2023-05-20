generatedReleases()

async function fetchLatestReleases() {
    const response = await fetch('https://api.github.com/repos/leepeuker/movary/releases?per_page=10')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
}

function generatedReleases() {
    let latestReleases = localStorage.getItem('latestReleases')
    if (latestReleases !== null) {
        latestReleases = JSON.parse(latestReleases)

        const maxAgeCachedReleasesInSeconds = 60;

        if (new Date() - new Date(latestReleases.time) < maxAgeCachedReleasesInSeconds * 1000) {
            document.getElementById('loadingSpinner').classList.add('d-none')

            latestReleases.data.forEach((latestRelease) => {
                addReleaseToList(latestRelease)
            })

            return
        }
    }

    fetchLatestReleases().then(latestReleases => {
        let usedLatestReleases = [];

        latestReleases.forEach((latestRelease) => {
            if (latestRelease.prerelease === true || latestRelease.draft === true) {
                return
            }

            document.getElementById('loadingSpinner').classList.add('d-none')
            addReleaseToList(latestRelease)

            usedLatestReleases.push(latestRelease)
        })

        localStorage.setItem('latestReleases', JSON.stringify({'data': usedLatestReleases, 'time': new Date().toString()}))
    }).catch((error) => {
        console.log(error)
        document.getElementById('loadingSpinner').classList.add('d-none')
        addAlert('alertReleasesDiv', 'Could not load latest releases. View them on <a href="https://github.com/leepeuker/movary/releases" target="_blank">Github</a> directly.', 'warning', false)
    })
}


function addReleaseToList(latestRelease) {
    let releasesList = document.getElementById('latestReleases')
    let releaseListItem = document.createElement('li');
    let releaseListItemTitle = document.createElement('span');

    releaseListItemTitle.innerHTML = latestRelease.name

    releaseListItem.appendChild(releaseListItemTitle);
    releaseListItem.classList.add('list-group-item', 'list-group-item-action');
    releaseListItem.dataset.body = marked.parse(latestRelease.body)
    releaseListItem.dataset.name = latestRelease.name
    releaseListItem.dataset.url = latestRelease.html_url
    releaseListItem.style.cursor = 'pointer'
    releaseListItem.setAttribute('onclick', "showReleaseModal(this)");

    releasesList.appendChild(releaseListItem);
}

function showReleaseModal(element) {
    const modal = new bootstrap.Modal('#appReleaseModal');

    document.getElementById('appReleaseModalTitle').innerHTML = element.dataset.name
    document.getElementById('appReleaseModalBody').innerHTML = element.dataset.body
    document.getElementById('appReleaseModalButtonViewGithub').href = element.dataset.url

    modal.show()
}
