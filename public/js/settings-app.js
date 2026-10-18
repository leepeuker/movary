const RELEASE_MARKDOWN_ALLOWED_TAGS = new Set([
    'A', 'BLOCKQUOTE', 'BR', 'CODE', 'DEL', 'EM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
    'HR', 'LI', 'OL', 'P', 'PRE', 'STRONG', 'UL',
])
const RELEASE_MARKDOWN_DISCARDED_TAGS = new Set([
    'BUTTON', 'EMBED', 'FORM', 'IFRAME', 'INPUT', 'MATH', 'OBJECT', 'SCRIPT', 'STYLE', 'SVG', 'TEMPLATE',
])

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
        addAlert(
            'alertReleasesDiv',
            'Could not load latest releases. View them on GitHub: https://github.com/leepeuker/movary/releases',
            'warning',
            false,
        )
    })
}


function addReleaseToList(latestRelease) {
    let releasesList = document.getElementById('latestReleases')
    let releaseListItem = document.createElement('li');
    let releaseListItemTitle = document.createElement('span');

    releaseListItemTitle.textContent = latestRelease.name || ''

    releaseListItem.appendChild(releaseListItemTitle);
    releaseListItem.classList.add('list-group-item', 'list-group-item-action');

    releaseListItem.dataset.body = latestRelease.body || ''
    releaseListItem.dataset.name = latestRelease.name || ''
    const releaseUrl = getSafeHttpUrl(latestRelease.html_url)
    if (releaseUrl !== null) {
        releaseListItem.dataset.url = releaseUrl
    }
    releaseListItem.style.cursor = 'pointer'
    releaseListItem.setAttribute('onclick', "showReleaseModal(this)");

    releasesList.appendChild(releaseListItem);
}

function showReleaseModal(element) {
    const modal = new bootstrap.Modal('#appReleaseModal');
    const modalBody = document.getElementById('appReleaseModalBody')
    const githubButton = document.getElementById('appReleaseModalButtonViewGithub')

    document.getElementById('appReleaseModalTitle').textContent = element.dataset.name
    modalBody.replaceChildren(createSanitizedMarkdownFragment(element.dataset.body))

    const releaseUrl = getSafeHttpUrl(element.dataset.url)
    githubButton.classList.toggle('d-none', releaseUrl === null)
    if (releaseUrl === null) {
        githubButton.removeAttribute('href')
    } else {
        githubButton.href = releaseUrl
    }

    modal.show()
}

function getSafeHttpUrl(value) {
    if (typeof value !== 'string' || value === '') {
        return null
    }

    try {
        const url = new URL(value, 'https://github.com')
        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
            return null
        }

        return url.href
    } catch (error) {
        return null
    }
}

function createSanitizedMarkdownFragment(markdown) {
    const parsedDocument = new DOMParser().parseFromString(marked.parse(markdown), 'text/html')
    const fragment = document.createDocumentFragment()

    parsedDocument.body.childNodes.forEach((node) => appendSanitizedNode(node, fragment))

    return fragment
}

function appendSanitizedNode(source, parent) {
    if (source.nodeType === Node.TEXT_NODE) {
        parent.append(document.createTextNode(source.textContent))
        return
    }

    if (source.nodeType !== Node.ELEMENT_NODE) {
        return
    }

    if (RELEASE_MARKDOWN_DISCARDED_TAGS.has(source.tagName)) {
        return
    }

    if (RELEASE_MARKDOWN_ALLOWED_TAGS.has(source.tagName) === false) {
        source.childNodes.forEach((child) => appendSanitizedNode(child, parent))
        return
    }

    const target = document.createElement(source.tagName.toLowerCase())
    if (source.tagName === 'A') {
        const safeUrl = getSafeHttpUrl(source.getAttribute('href'))
        if (safeUrl !== null) {
            target.href = safeUrl
            target.target = '_blank'
            target.rel = 'noopener noreferrer'
        }
        if (source.hasAttribute('title')) {
            target.title = source.getAttribute('title')
        }
    }

    source.childNodes.forEach((child) => appendSanitizedNode(child, target))
    parent.append(target)
}
